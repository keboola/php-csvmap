<?php

use Keboola\CsvMap\Mapper;

class MapperShorthandTest extends PHPUnit_Framework_TestCase
{

    public function testParseShorthand()
    {
        $config = [
            'id' => 'id',
            'timestamp' => 'timestamp',
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $file = $parser->getCsvFiles()['root'];

        $expected = <<<CSV
"id","timestamp"
"1","1234567890"\n
CSV;
        $this->assertEquals($expected, file_get_contents($file->getPathname()));
    }

    public function testParseShorthandWithRelation()
    {
        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'pk',
                    'primaryKey' => true,
                ]
            ],
            'timestamp' => 'timestamp',
            'reactions' => [
                'type' => 'table',
                'destination' => 'reactions',
                'tableMapping' => [
                    'user.id' => 'id',
                    'user.username' => 'username',
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);

        $file1 = $parser->getCsvFiles()['root'];
        $expected1 = <<<CSV
"pk","timestamp"
"1","1234567890"\n
CSV;
        $this->assertEquals($expected1, file_get_contents($file1->getPathname()));


        $file2 = $parser->getCsvFiles()['reactions'];

        $expected2 = <<<CSV
"id","username","root_pk"
"456","jose","1"
"789","mike","1"\n
CSV;
        $this->assertEquals($expected2, file_get_contents($file2->getPathname()));
    }

    protected function getSampleData()
    {
        $json = <<<JSON
[
    {
        "timestamp": 1234567890,
        "id": 1,
        "text": "asdf",
        "user": {
            "id": 123,
            "username": "alois"
        },
        "reactions": [
            {
                "user": {
                    "id": 456,
                    "username": "jose"
                }
            },
            {
                "user": {
                    "id": 789,
                    "username": "mike"
                }
            }
        ]
    }
]
JSON;
        return json_decode($json, true);
    }
}
