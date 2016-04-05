<?php

use Keboola\CsvMap\Mapper;

class MapperTest extends PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user/id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id'
                        ],
                        'delimiter' => '/'
                    ]
                ],
//                 'parentKey' => [
//                     'primaryKey' => true,
//                     //'columns' => ['id', 'user_id'],
//                     //'hash' => true
//                 ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['root', 'post_reactions'], array_keys($result));
        foreach($result as $name => $file) {
            $this->assertFileEquals('./tests/data/' . $name, $file->getPathname());
        }
    }

    public function testParseNoPK()
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id'
                ]
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id'
                        ]
                    ]
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        foreach($result as $name => $file) {
            $this->assertFileEquals('./tests/data/noPK/' . $name, $file->getPathname());
        }
    }

    public function testParseCompositePK()
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id',
                    'primaryKey' => true
                ]
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id'
                        ]
                    ]
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        foreach($result as $name => $file) {
            $this->assertFileEquals('./tests/data/compositePK/' . $name, $file->getPathname());
        }
    }

    public function testParentKeyPK()
    {
        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id',
                            'primaryKey' => true
                        ]
                    ]
                ],
                'parentKey' => [
                    'primaryKey' => true
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['user_id', 'root_pk'], $result['post_reactions']->getPrimaryKey(true));
    }

    public function testEmptyArray()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'arr' => [
                'type' => 'table',
                'destination' => 'children',
                'tableMapping' => [
                    'child_id' => [
                        'mapping' => [
                            'destination' => 'child_id'
                        ]
                    ]
                ]
            ]
        ];

        $data = [
            (object) [
                'id' => 1
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['"id","children"' . PHP_EOL, '"1",""' . PHP_EOL], file($result['root']));
    }

    public function testEmptyString()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'str' => [
                'mapping' => [
                    'destination' => 'text'
                ]
            ]
        ];

        $data = [
            (object) [
                'id' => 1,
                'str' => 'asdf'
            ],
            (object) [
                'id' => 2
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","text"' . PHP_EOL,
                '"1","asdf"' . PHP_EOL,
                '"2",""' . PHP_EOL
            ],
            file($result['root'])
        );
    }

    public function testPrimaryKey()
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['post_id'], $result['root']->getPrimaryKey(true));
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Key 'mapping.destination' must be set for each column.
     */
    public function testNoMappingKeyColumn()
    {

        $config = [
            'timestamp' => [
                'type' => 'column'
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Key 'destination' must be set for each table.
     */
    public function testNoDestinationTable()
    {

        $config = [
            'arr' => [
                'type' => 'table'
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Key 'tableMapping' must be set for each table.
     */
    public function testNoTableMapping()
    {
        $config = [
            'reactions' => [
                'type' => 'table',
                'destination' => 'children'
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Key 'destination' must be set for each table.
     */
    public function testNoDestinationNestedTable()
    {
        $config = [
            'reactions' => [
                'type' => 'table',
                'tableMapping' => [
                    'child_id' => [
                        'mapping' => [
                            'destination' => 'child_id'
                        ]
                    ]
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    public function testDataInjection()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol'
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data, ['userData' => 'blah']);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1","blah"' . PHP_EOL
            ],
            file($result['root'])
        );
    }

    public function testDataInjectionPK()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                    'primaryKey' => true
                ]
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id'
                        ]
                    ]
                ]
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol',
                    'primaryKey' => true
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data, ['userData' => 'blah']);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1","blah"' . PHP_EOL
            ],
            file($result['root'])
        );
        $this->assertEquals(['id','userCol'], $result['root']->getPrimaryKey(true));

        $this->assertEquals(
            [
                '"user_id","root_pk"' . PHP_EOL,
                '"456","1,blah"' . PHP_EOL,
                '"789","1,blah"' . PHP_EOL
            ],
            file($result['post_reactions'])
        );
    }

    public function testDataInjectionNoData()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol'
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1",""' . PHP_EOL
            ],
            file($result['root'])
        );
    }

    protected function getSampleData()
    {
        return [
            (object) [
                'timestamp' => 1234567890,
                'id' => 1,
                'text' => 'asdf',
                'user' => (object) [
                    'id' => 123,
                    'username' => 'alois'
                ],
                'reactions' => [
                    (object) [
                        'user' => (object) [
                            'id' => 456,
                            'username' => 'jose'
                        ]
                    ],
                    (object) [
                        'user' => (object) [
                            'id' => 789,
                            'username' => 'mike'
                        ]
                    ]
                ]
            ]
        ];
    }
}
