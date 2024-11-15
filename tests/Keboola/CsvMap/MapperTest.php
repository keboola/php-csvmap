<?php

declare(strict_types=1);

namespace Keboola\CsvMap;

use Keboola\CsvMap\Exception\BadConfigException;
use Keboola\CsvMap\Exception\BadDataException;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testParse(): void
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp',
                ],
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true,
                ],
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id',
                ],
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user/id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id',
                        ],
                        'delimiter' => '/',
                    ],
                ],
//                 'parentKey' => [
//                     'primaryKey' => true,
//                     //'columns' => ['id', 'user_id'],
//                     //'hash' => true
//                 ]
            ],
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

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testParseNoPK(): void
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp',
                ],
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                ],
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id',
                ],
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id',
                        ],
                    ],
                ],
            ],
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

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testParseCompositePK(): void
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp',
                ],
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true,
                ],
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id',
                    'primaryKey' => true,
                ],
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id',
                        ],
                    ],
                ],
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        foreach ($result as $name => $file) {
            $this->assertFileEquals('./tests/data/compositePK/' . $name, $file->getPathname());
        }
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testParentKeyPK(): void
    {
        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true,
                ],
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id',
                            'primaryKey' => true,
                        ],
                    ],
                ],
                'parentKey' => [
                    'primaryKey' => true,
                ],
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['user_id', 'root_pk'], $result['post_reactions']->getPrimaryKey(true));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testParentKeyDestination(): void
    {
        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true,
                ],
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id',
                            'primaryKey' => true,
                        ],
                    ],
                ],
                'parentKey' => [
                    'destination' => 'post_id',
                ],
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $file = file($result['post_reactions']->getPathName());
        if ($file === false) {
            $this->fail();
        }
        $this->assertEquals('"user_id","post_id"' . PHP_EOL, $file[0]);
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testEmptyArray(): void
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                ],
            ],
            'arr' => [
                'type' => 'table',
                'destination' => 'children',
                'tableMapping' => [
                    'child_id' => [
                        'mapping' => [
                            'destination' => 'child_id',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            (object) [
                'id' => 1,
            ],
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['"id","children"' . PHP_EOL, '"1",""' . PHP_EOL], file($result['root']->getPathName()));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testEmptyString(): void
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                ],
            ],
            'str' => [
                'mapping' => [
                    'destination' => 'text',
                ],
            ],
        ];

        $data = [
            (object) [
                'id' => 1,
                'str' => 'asdf',
            ],
            (object) [
                'id' => 2,
            ],
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","text"' . PHP_EOL,
                '"1","asdf"' . PHP_EOL,
                '"2",""' . PHP_EOL,
            ],
            file($result['root']->getPathName())
        );
    }

    public function testDate(): void
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                ],
            ],
            'birthDate' => [
                'type' => 'date',
                'mapping' => [
                    'destination' => 'birthTime',
                ],
            ],
        ];

        $data = [
            (object) [
                'id' => 1,
                'birthDate' => '1963-07-10T00:00:00.000Z',
            ],
            (object) [
                'id' => 2,
            ],
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","birthTime"' . PHP_EOL,
                '"1","-204508800"' . PHP_EOL,
                '"2",""' . PHP_EOL,
            ],
            file($result['root']->getPathName())
        );
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testPrimaryKey(): void
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp',
                ],
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true,
                ],
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id',
                ],
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['post_id'], $result['root']->getPrimaryKey(true));
    }

    /**
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     */
    public function testNoMappingKeyColumn(): void
    {

        $config = [
            'timestamp' => [
                'type' => 'column',
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);

        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessage("Key 'mapping.destination' is not set for column 'timestamp'.");
        $parser->parse($data);
    }

    /**
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     */
    public function testNoDestinationTable(): void
    {

        $config = [
            'arr' => [
                'type' => 'table',
            ],
        ];

        $data = $this->getSampleData();
        $parser = new Mapper($config);

        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessage("Key 'destination' is not set for table 'arr'.");
        $parser->parse($data);
    }

    /**
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     */
    public function testNoTableMapping(): void
    {
        $config = [
            'reactions' => [
                'type' => 'table',
                'destination' => 'children',
            ],
        ];

        $data = $this->getSampleData();
        $parser = new Mapper($config);

        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessage("Key 'tableMapping' is not set for table 'reactions'.");
        $parser->parse($data);
    }

    /**
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     */
    public function testNoDestinationNestedTable(): void
    {
        $config = [
            'reactions' => [
                'type' => 'table',
                'tableMapping' => [
                    'child_id' => [
                        'mapping' => [
                            'destination' => 'child_id',
                        ],
                    ],
                ],
            ],
        ];

        $data = $this->getSampleData();
        $parser = new Mapper($config);

        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessage("Key 'destination' is not set for table 'reactions'.");
        $parser->parse($data);
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testDataInjection(): void
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                ],
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol',
                ],
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data, ['userData' => 'blah']);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1","blah"' . PHP_EOL,
            ],
            file($result['root']->getPathName())
        );
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testDataInjectionPK(): void
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                    'primaryKey' => true,
                ],
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id',
                        ],
                    ],
                ],
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol',
                    'primaryKey' => true,
                ],
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data, ['userData' => 'blah']);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1","blah"' . PHP_EOL,
            ],
            file($result['root']->getPathName())
        );
        $this->assertEquals(['id', 'userCol'], $result['root']->getPrimaryKey(true));

        $this->assertEquals(
            [
                '"user_id","root_pk"' . PHP_EOL,
                '"456","1,blah"' . PHP_EOL,
                '"789","1,blah"' . PHP_EOL,
            ],
            file($result['post_reactions']->getPathName())
        );
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testDataInjectionNoData(): void
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                ],
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol',
                ],
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1",""' . PHP_EOL,
            ],
            file($result['root']->getPathName())
        );
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testUserDataPropagation(): void
    {
        $data = $this->getSampleData();

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                ],
            ],
            'user' => [
                'type' => 'table',
                'destination' => 'users',
                'tableMapping' => [
                    'id' => [
                        'mapping' => [
                            'destination' => 'id',
                            'primaryKey' => true,
                        ],
                    ],
                    'username' => [
                        'mapping' => [
                            'destination' => 'username',
                        ],
                    ],
                    'keboola_source' => [
                        'type' => 'user',
                        'mapping' => [
                            'destination' => 'keboola_source',
                        ],
                    ],
                ],
                'parentKey' => [
                    'disable' => true,
                ],
            ],
            'user.id' => [
                'mapping' => [
                    'destination' => 'user_id',
                ],
            ],
            'keboola_source' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'keboola_source',
                ],
            ],
        ];

        $parser = new Mapper($config);
        $parser->parse($data, [
            'keboola_source' => 'search',
        ]);
        $result = $parser->getCsvFiles();

        $this->assertEquals([
            '"id","user_id","keboola_source"' . PHP_EOL,
            '"1","123","search"' . PHP_EOL,
        ], file($result['root']->getPathName()));
        $this->assertEquals([
            '"id","username","keboola_source"' . PHP_EOL,
            '"123","alois","search"' . PHP_EOL,
        ], file($result['users']->getPathName()));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testObjectToTable(): void
    {
        $data = $this->getSampleData();

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                ],
            ],
            'user' => [
                'type' => 'table',
                'destination' => 'users',
                'tableMapping' => [
                    'id' => [
                        'mapping' => [
                            'destination' => 'id',
                            'primaryKey' => true,
                        ],
                    ],
                    'username' => [
                        'mapping' => [
                            'destination' => 'username',
                        ],
                    ],
                ],
                'parentKey' => [
                    'disable' => true,
                ],
            ],
            'user.id' => [
                'mapping' => [
                    'destination' => 'user_id',
                ],
            ],
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            ['"id","user_id"' . PHP_EOL, '"1","123"' . PHP_EOL],
            file($result['root']->getPathName())
        );
        $this->assertEquals(
            ['"id","username"' . PHP_EOL, '"123","alois"' . PHP_EOL],
            file($result['users']->getPathName())
        );
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testDisableParentKey(): void
    {

        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                ],
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id',
                        ],
                    ],
                ],
                'parentKey' => [
                    'disable' => true,
                ],
            ],
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals([
            '"post_id"' . PHP_EOL,
            '"1"' . PHP_EOL,
        ], file($result['root']->getPathName()));
        $this->assertEquals([
            '"user_id"' . PHP_EOL, '"456"' . PHP_EOL,
            '"789"' . PHP_EOL,
        ], file($result['post_reactions']->getPathName()));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testChildSameParser(): void
    {
        $data = [
            (object) [
                'id' => 1,
                'child' => (object) [
                    'id' => 1.1,
                ],
                'arrChild' => [ // redundant?
                    (object) ['id' => '1.2'],
                ],
            ],
        ];

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'post_id',
                ],
            ],
            'child' => [
                'type' => 'table',
                'destination' => 'items',
                'parentKey' => [
                    'disable' => true,
                ],
            ],
            'arrChild' => [
                'type' => 'table',
                'destination' => 'items',
                'parentKey' => [
                    'disable' => true,
                ],
            ],
        ];

        $parser = new Mapper($config, true, 'items');
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals([
            '"post_id"' . PHP_EOL,
            '"1.1"' . PHP_EOL,
            '"1.2"' . PHP_EOL,
            '"1"' . PHP_EOL,
        ], file($result['items']->getPathName()));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testArrayItemToColumn(): void
    {
        $config = [
            'arr.0' => [
                'mapping' => [
                    'destination' => 'first_arr_item',
                ],
            ],
        ];

        $data = [
            (object) [
                'arr' => [
                    'one', 'two',
                ],
            ],
        ];

        $parser = new Mapper($config);
        $parser->parse($data);

        $this->assertEquals(
            ['"first_arr_item"' . PHP_EOL, '"one"' . PHP_EOL],
            file($parser->getCsvFiles()['root']->getPathName())
        );
    }

    /**
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testObjectToColumnError(): void
    {
        $config = [
            'user' => [
                'mapping' => [
                    'destination' => 'user',
                ],
            ],
        ];

        $parser = new Mapper($config);

        $this->expectException(BadDataException::class);
        $this->expectExceptionMessage("Error writing 'user' column: Cannot write data into column: ");
        $parser->parse($this->getSampleData());
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testDeepNestedTable(): void
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
                            'id' => 'gcid',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            (object) [
                'id' => 1,
                'child' => [
                    (object) [
                        'id' => 2,
                        'grandchild' => [
                            (object) [
                                'id' => 3,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $parser = new Mapper($config);
        $parser->parse($data);

        $this->assertEquals(['root', 'child', 'grandchild'], array_keys($parser->getCsvFiles()));
    }

    /**
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testMixedDataError(): void
    {
        $config = [
            'id' => 'id',
            'arr' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'arrStr',
                ],
            ],
        ];

        $data = $this->getMixedData();

        $parser = new Mapper($config);

        $this->expectException(BadDataException::class);
        $this->expectExceptionMessage("Error writing 'arrStr' column: Cannot write data into column: array");
        $parser->parse($data);
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testArrayToString(): void
    {
        $config = [
            'id' => 'id',
            'arr' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'str',
                ],
                'forceType' => true,
            ],
        ];

        $data = $this->getMixedData();

        $parser = new Mapper($config);
        $parser->parse($data);

        $expected = [
            '"id","str"' . PHP_EOL,
            '"1","[1.1,1.2]"' . PHP_EOL,
            '"2","2.1"' . PHP_EOL,
        ];

        $this->assertEquals($expected, file($parser->getCsvFiles()['root']->getPathName()));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testStringToArray(): void
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
                    'primaryKey' => true,
                ],
            ],
            'arr' => [
                'type' => 'table',
                'destination' => 'arr',
                'tableMapping' => [
                    '.' => 'data',
                ],
                'forceType' => true,
            ],
        ];

        $data = $this->getMixedData();

        $parser = new Mapper($config);
        $parser->parse($data);

        $root = [
            '"id"' . PHP_EOL,
            '"1"' . PHP_EOL,
            '"2"' . PHP_EOL,
        ];

        $arr = [
            '"data","root_pk"' . PHP_EOL,
            '"1.1","1"' . PHP_EOL,
            '"1.2","1"' . PHP_EOL,
            '"2.1","2"' . PHP_EOL,
        ];

        $this->assertEquals($root, file($parser->getCsvFiles()['root']->getPathName()));
        $this->assertEquals($arr, file($parser->getCsvFiles()['arr']->getPathName()));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testArrayToTable(): void
    {
        $config = [
            'rows' => [
                'type' => 'table',
                'destination' => 'report-rows',
                'tableMapping' => [
                    '0' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'date',
                        ],
                    ],
                    '1' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'clicks',
                        ],
                    ],
                ],
            ],
        ];

        $data = json_decode('[{
            "rows": [
                ["2017-05-27","83008"],
                ["2017-05-28","105723"]
            ]
        }]');

        $parser = new Mapper($config);
        $parser->parse($data);

        $expected = [
            '"date","clicks","root_pk"' . PHP_EOL,
            '"2017-05-27","83008","20afe46b23b7afa04d50a036bc3b9021"' . PHP_EOL,
            '"2017-05-28","105723","20afe46b23b7afa04d50a036bc3b9021"' . PHP_EOL,
        ];

        $this->assertEquals($expected, file($parser->getCsvFiles()['report-rows']->getPathName()));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testDontWriteHeader(): void
    {
        $mapping = [
            'id' => 'id',
            'timestamp' => 'time',
        ];

        $data = $this->getSampleDataSimple();
        $parser = new Mapper($mapping, false); // <<<<<< false
        $parser->parse($data);
        $file = $parser->getCsvFiles()['root'];

        $this->assertEquals("\"1\",\"1234567890\"\n", file_get_contents($file->getPathname()));
        $this->assertEquals(['id', 'time'], $file->getHeader());
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testScalarArrayToSeparatedTable(): void
    {
        $mapping = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'id',
                    'primaryKey' => true,
                ],

            ],
            'title' => 'title',
            'actors' => [
                'type' => 'table',
                'destination' => 'actor',
                'tableMapping' => [
                    '' => 'name', // <<<<< empty string means "self"
                ],
            ],
        ];

        $data = [
            ['id' => 1, 'title' => 'Rush', 'actors' => ['Daniel Bruhl', 'Chris Hemsworth', 'Olivia Wilde']],
            ['id' => 2, 'title' => 'Prisoners', 'actors' => ['Hugh Jackman', 'Jake Gyllenhaal', 'Viola Davis']],
            ['id' => 3, 'title' => 'Insidious 2', 'actors' => ['Patrick Wilson', 'Rose Byrne', 'Barbara Hershey']],
        ];

        $parser = new Mapper($mapping);
        $parser->parse($data);

        $files = $parser->getCsvFiles();
        $this->assertCount(2, $files);

        // Check root file
        $expectedRoot = [
            '"id","title"' . PHP_EOL,
            '"1","Rush"' . PHP_EOL,
            '"2","Prisoners"' . PHP_EOL,
            '"3","Insidious 2"' . PHP_EOL,
        ];
        $this->assertEquals($expectedRoot, file($files['root']->getPathName()));

        // Check actors file
        $expectedActor = [
            '"name","root_pk"' . PHP_EOL,
            '"Daniel Bruhl","1"' . PHP_EOL,
            '"Chris Hemsworth","1"' . PHP_EOL,
            '"Olivia Wilde","1"' . PHP_EOL,
            '"Hugh Jackman","2"' . PHP_EOL,
            '"Jake Gyllenhaal","2"' . PHP_EOL,
            '"Viola Davis","2"' . PHP_EOL,
            '"Patrick Wilson","3"' . PHP_EOL,
            '"Rose Byrne","3"' . PHP_EOL,
            '"Barbara Hershey","3"' . PHP_EOL,
        ];
        $this->assertEquals($expectedActor, file($files['actor']->getPathName()));
    }

    /**
     * @return array<int, object>
     */
    protected function getMixedData(): array
    {
        return [
            (object) [
                'id' => 1,
                'arr' => [
                    1.1,
                    1.2,
                ],
            ],
            (object) [ // poor data
                'id' => 2,
                'arr' => 2.1,
            ],
        ];
    }

    /**
     * @return array<int, object>
     */
    protected function getSampleData(): array
    {
        return [
            (object) [
                'timestamp' => 1234567890,
                'id' => 1,
                'text' => 'asdf',
                'user' => (object) [
                    'id' => 123,
                    'username' => 'alois',
                ],
                'reactions' => [
                    (object) [
                        'user' => (object) [
                            'id' => 456,
                            'username' => 'jose',
                        ],
                    ],
                    (object) [
                        'user' => (object) [
                            'id' => 789,
                            'username' => 'mike',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getSampleDataSimple(): array
    {
        $json = <<<JSON
[
    {
        "timestamp": 1234567890,
        "id": 1,
        "reactions": []
    }
]
JSON;
        return json_decode($json, true);
    }
}
