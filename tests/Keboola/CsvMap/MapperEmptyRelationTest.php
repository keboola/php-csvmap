<?php

declare(strict_types=1);

namespace Keboola\CsvMap;

use PHPUnit\Framework\TestCase;

class MapperEmptyRelationTest extends TestCase
{
    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\Csv\Exception
     */
    public function testParseEmptyRelation(): void
    {
        $mapping = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'pk',
                    'primaryKey' => true,
                ],
            ],
            'timestamp' => 'timestamp',
            'reactions' => [
                'type' => 'table',
                'destination' => 'reactions',
                'tableMapping' => [
                    'user.id' => 'id',
                    'user.username' => 'username',
                ],
            ],
        ];

        // one row
        $data = $this->getSampleDataSimple();

        $parser = new Mapper($mapping);
        $parser->parse($data);

        $file1 = $parser->getCsvFiles()['root'];
        $expected1 = <<<CSV
"pk","timestamp"
"1","1234567890"\n
CSV;
        $this->assertFileExists($file1->getPathname());
        $this->assertEquals($expected1, file_get_contents($file1->getPathname()));

        $file2 = $parser->getCsvFiles()['reactions'];

        $expected2 = <<<CSV
"id","username","root_pk"\n
CSV;
        $this->assertFileExists($file2->getPathname());
        $this->assertEquals($expected2, file_get_contents($file2->getPathname()));

        // multiple rows
        $data = $this->getSampleDataMulti();

        $parser = new Mapper($mapping);
        $parser->parse($data);

        $file1 = $parser->getCsvFiles()['root'];
        $expected1 = <<<CSV
"pk","timestamp"
"1","1234567890"
"2","9876543210"\n
CSV;
        $this->assertFileExists($file1->getPathname());
        $this->assertEquals($expected1, file_get_contents($file1->getPathname()));

        $file2 = $parser->getCsvFiles()['reactions'];

        $this->assertFileExists($file2->getPathname());
        $this->assertEquals($expected2, file_get_contents($file2->getPathname()));
    }

    /**
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function testParseEmptyRelationFew(): void
    {
        $mapping = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'pk',
                    'primaryKey' => true,
                ],
            ],
            'timestamp' => 'timestamp',
            'reactions' => [
                'type' => 'table',
                'destination' => 'reactions',
                'tableMapping' => [
                    'user.id' => 'id',
                    'user.username' => 'username',
                ],
            ],
        ];

        // multiple rows
        $data = $this->getSampleDataMultiFirstEmpty();

        $parser = new Mapper($mapping);
        $parser->parse($data);

        $file1 = $parser->getCsvFiles()['root'];
        $expected1 = <<<CSV
"pk","timestamp"
"1","1234567891"
"2","1234567892"
"3","1234567893"
"4","1234567894"\n
CSV;
        $this->assertFileExists($file1->getPathname());
        $this->assertEquals($expected1, file_get_contents($file1->getPathname()));

        $file2 = $parser->getCsvFiles()['reactions'];

        $expected2 = <<<CSV
"id","username","root_pk"
"456","jose","2"
"789","mary","4"\n
CSV;
        $this->assertFileExists($file2->getPathname());
        $this->assertEquals($expected2, file_get_contents($file2->getPathname()));
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

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getSampleDataMulti(): array
    {
        $json = <<<JSON
[
    {
        "id": 1,
        "timestamp": 1234567890,
        "reactions": []
    },
    {
        "id": 2,
        "timestamp": 9876543210,
        "reactions": []
    }
]
JSON;
        return json_decode($json, true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getSampleDataMultiFirstEmpty(): array
    {
        $json = <<<JSON
[
    {
        "id": 1,
        "timestamp": 1234567891,
        "reactions": []
    },
    {
        "id": 2,
        "timestamp": 1234567892,
        "reactions": [
            {
                "user": {
                    "id": 456,
                    "username": "jose"
                }
            }
        ]
    },
    {
        "id": 3,
        "timestamp": 1234567893,
        "reactions": []
    },
    {
        "id": 4,
        "timestamp": 1234567894,
        "reactions": [
            {
                "user": {
                    "id": 789,
                    "username": "mary"
                }
            }
        ]
    }
]
JSON;
        return json_decode($json, true);
    }
}
