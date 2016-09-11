<?php

namespace Keboola\CsvMap;

class MapperTest extends \PHPUnit_Framework_TestCase
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
        foreach ($result as $name => $file) {
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

        $this->assertEquals(['root', 'post_reactions'], array_keys($result));
        foreach ($result as $name => $file) {
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

        foreach ($result as $name => $file) {
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

    public function testParentKeyDestination()
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
                    'destination' => 'post_id'
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals('"user_id","post_id"' . PHP_EOL, file($result['post_reactions'])[0]);
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
     * @expectedExceptionMessage Key 'mapping.destination' is not set for column 'timestamp'.
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
     * @expectedExceptionMessage Key 'destination' is not set for table 'arr'.
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
     * @expectedExceptionMessage Key 'tableMapping' is not set for table 'reactions'.
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
     * @expectedExceptionMessage Key 'destination' is not set for table 'reactions'.
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

    public function testUserDataPropagation()
    {
        $data = $this->getSampleData();

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'user' => [
                'type' => 'table',
                'destination' => 'users',
                'tableMapping' => [
                    'id' => [
                        'mapping' => [
                            'destination' => 'id',
                            'primaryKey' => true
                        ]
                    ],
                    'username' => [
                        'mapping' => [
                            'destination' => 'username'
                        ]
                    ],
                    'keboola_source' => [
                        'type' => 'user',
                        'mapping' => [
                            'destination' => 'keboola_source'
                        ]
                    ]
                ],
                'parentKey' => [
                    'disable' => true
                ]
            ],
            'user.id' => [
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ],
            'keboola_source' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'keboola_source'
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data, [
            'keboola_source' => 'search',
        ]);
        $result = $parser->getCsvFiles();

        $this->assertEquals([
            '"id","user_id","keboola_source"' . PHP_EOL,
            '"1","123","search"' . PHP_EOL
        ], file($result['root']));
        $this->assertEquals([
            '"id","username","keboola_source"' . PHP_EOL,
            '"123","alois","search"' . PHP_EOL
        ], file($result['users']));
    }

    public function testObjectToTable()
    {
        $data = $this->getSampleData();

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'user' => [
                'type' => 'table',
                'destination' => 'users',
                'tableMapping' => [
                    'id' => [
                        'mapping' => [
                            'destination' => 'id',
                            'primaryKey' => true
                        ]
                    ],
                    'username' => [
                        'mapping' => [
                            'destination' => 'username'
                        ]
                    ]
                ],
                'parentKey' => [
                    'disable' => true
                ]
            ],
            'user.id' => [
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['"id","user_id"' . PHP_EOL, '"1","123"' . PHP_EOL], file($result['root']));
        $this->assertEquals(['"id","username"' . PHP_EOL, '"123","alois"' . PHP_EOL], file($result['users']));
    }

    public function testDisableParentKey()
    {

        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id'
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
                ],
                'parentKey' => [
                    'disable' => true
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals([
            '"post_id"' . PHP_EOL,
            '"1"' . PHP_EOL
        ], file($result['root']));
        $this->assertEquals([
            '"user_id"' . PHP_EOL, '"456"' . PHP_EOL,
            '"789"' . PHP_EOL
        ], file($result['post_reactions']));
    }

    public function testChildSameParser()
    {
        $data = [
            (object) [
                'id' => 1,
                'child' => (object) [
                    'id' => 1.1
                ],
                'arrChild' => [ // redundant?
                    (object) ['id' => '1.2']
                ]
            ]
        ];

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'post_id'
                ]
            ],
            'child' => [
                'type' => 'table',
                'destination' => 'items',
                'parentKey' => [
                    'disable' => true
                ]
            ],
            'arrChild' => [
                'type' => 'table',
                'destination' => 'items',
                'parentKey' => [
                    'disable' => true
                ]
            ]
        ];

        $parser = new Mapper($config, 'items');
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals([
            '"post_id"' . PHP_EOL,
            '"1.1"' . PHP_EOL,
            '"1.2"' . PHP_EOL,
            '"1"' . PHP_EOL
        ], file($result['items']));
    }

    public function testArrayItemToColumn()
    {
        $config = [
            'arr.0' => [
                'mapping' => [
                    'destination' => 'first_arr_item'
                ]
            ]
        ];

        $data = [
            (object) [
                'arr' => [
                    'one', 'two'
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);

        $this->assertEquals(['"first_arr_item"' . PHP_EOL, '"one"' . PHP_EOL], file($parser->getCsvFiles()['root']));
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadDataException
     * @expectedExceptionMessage Error writing 'user' column: Cannot write object into a column
     */
    public function testObjectToColumnError()
    {
        $config = [
            'user' => [
                'mapping' => [
                    'destination' => 'user'
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($this->getSampleData());
    }

    public function testDeepNestedTable()
    {
        $config = [
            'id' => 'id',
            'child' => [
                'type' => 'table',
                'destination' => 'child',
                'tableMapping' => [
                    'id' => 'cid',
                    'grandchild' => [
                        'type' => 'table',
                        'destination' => 'grandchild',
                        'tableMapping' => [
                            'id' => 'gcid'
                        ]
                    ]
                ]
            ]
        ];

        $data = [
            (object) [
                'id' => 1,
                'child' => [
                    (object) [
                        'id' => 2,
                        'grandchild' => [
                            (object) [
                                'id' => 3
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);

        $this->assertEquals(['root','child','grandchild'], array_keys($parser->getCsvFiles()));
    }

    /**
     * @expectedException Keboola\CsvMap\Exception\BadDataException
     * @expectedExceptionMessage Error writing 'arrStr' column: Cannot write array into a column
     */
    public function testMixedDataError()
    {
        $config = [
            'id' => 'id',
            'arr' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'arrStr'
                ]
            ]
        ];

        $data = $this->getMixedData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    public function testArrayToString()
    {
        $config = [
            'id' => 'id',
            'arr' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'str'
                ],
                'forceType' => true
            ]
        ];

        $data = $this->getMixedData();

        $parser = new Mapper($config);
        $parser->parse($data);

        $expected = [
            '"id","str"' . PHP_EOL,
            '"1","[1.1,1.2]"' . PHP_EOL,
            '"2","2.1"' . PHP_EOL
        ];

        $this->assertEquals($expected, file($parser->getCsvFiles()['root']));
    }

    public function testStringToArray()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                    'primaryKey' => true
                ]
            ],
            'arr' => [
                'type' => 'table',
                'destination' => 'arr',
                'tableMapping' => [
                    '.' => 'data'
                ],
                'forceType' => true
            ]
        ];

        $data = $this->getMixedData();

        $parser = new Mapper($config);
        $parser->parse($data);

        $root = [
            '"id"' . PHP_EOL,
            '"1"' . PHP_EOL,
            '"2"' . PHP_EOL
        ];

        $arr = [
            '"data","root_pk"' . PHP_EOL,
            '"1.1","1"' . PHP_EOL,
            '"1.2","1"' . PHP_EOL,
            '"2.1","2"' . PHP_EOL
        ];

        $this->assertEquals($root, file($parser->getCsvFiles()['root']));
        $this->assertEquals($arr, file($parser->getCsvFiles()['arr']));
    }

    protected function getMixedData()
    {
        return [
            (object) [
                'id' => 1,
                'arr' => [
                    1.1,
                    1.2
                ]
            ],
            (object) [ // poor data
                'id' => 2,
                'arr' => 2.1
            ]
        ];
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
