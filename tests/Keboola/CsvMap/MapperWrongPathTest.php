<?php

namespace Keboola\CsvMap;

use PHPUnit\Framework\TestCase;

class MapperWrongPathTest extends TestCase
{
    public function testParseWrongMain()
    {
        $mapping = [
            'nonExistent1' => 'nonExistent1',
            'nonExistent2' => 'nonExistent2',
        ];

        // one row
        $data = $this->getSampleDataSimple();

        $parser = new Mapper($mapping);
        $parser->parse($data);
        $file = $parser->getCsvFiles()['root'];

        $expected = <<<CSV
"nonExistent1","nonExistent2"
"",""\n
CSV;
        $this->assertEquals($expected, file_get_contents($file->getPathname()));

        // multiple rows
        $data = $this->getSampleDataMulti();

        $parser = new Mapper($mapping);
        $parser->parse($data);
        $file = $parser->getCsvFiles()['root'];

        $expected = <<<CSV
"nonExistent1","nonExistent2"
"",""
"",""\n
CSV;
        $this->assertEquals($expected, file_get_contents($file->getPathname()));
    }

    public function testParseWrongRelation()
    {
        $mapping = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'pk',
                    'primaryKey' => true,
                ]
            ],
            'timestamp' => 'timestamp',
            'nonexistent' => [
                'type' => 'table',
                'destination' => 'nonexistent',
                'tableMapping' => [
                    'user.id' => 'id',
                    'user.username' => 'username',
                ]
            ]
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


        $file2 = $parser->getCsvFiles()['nonexistent'];

        $expected2 = <<<CSV
"id","username","root_pk"
"","","1"\n
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


        $file2 = $parser->getCsvFiles()['nonexistent'];

        $expected2 = <<<CSV
"id","username","root_pk"
"","","1"
"","","2"\n
CSV;
        $this->assertFileExists($file2->getPathname());
        $this->assertEquals($expected2, file_get_contents($file2->getPathname()));
    }

    public function testParseWrongBothMainAndRelation()
    {
        $mapping = [
            'nonexistent1' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'nonexistent1',
                    'primaryKey' => true,
                ]
            ],
            'nonexistent2' => 'nonexistent2',
            'nonexistent3' => [
                'type' => 'table',
                'destination' => 'nonexistent3',
                'tableMapping' => [
                    'user.id' => 'id',
                    'user.username' => 'username',
                ]
            ]
        ];

        // one row
        $data = $this->getSampleDataSimple();

        $parser = new Mapper($mapping);
        $parser->parse($data);

        $file1 = $parser->getCsvFiles()['root'];
        $expected1 = <<<CSV
"nonexistent1","nonexistent2"
"",""\n
CSV;
        $this->assertFileExists($file1->getPathname());
        $this->assertEquals($expected1, file_get_contents($file1->getPathname()));


        $file2 = $parser->getCsvFiles()['nonexistent3'];

        $expected2 = <<<CSV
"id","username","root_pk"
"","",""\n
CSV;
        $this->assertFileExists($file2->getPathname());
        $this->assertEquals($expected2, file_get_contents($file2->getPathname()));

        // multiple rows
        $data = $this->getSampleDataMulti();

        $parser = new Mapper($mapping);
        $parser->parse($data);

        $file1 = $parser->getCsvFiles()['root'];
        $expected1 = <<<CSV
"nonexistent1","nonexistent2"
"",""
"",""\n
CSV;
        $this->assertFileExists($file1->getPathname());
        $this->assertEquals($expected1, file_get_contents($file1->getPathname()));


        $file2 = $parser->getCsvFiles()['nonexistent3'];

        $expected2 = <<<CSV
"id","username","root_pk"
"","",""
"","",""\n
CSV;
        $this->assertFileExists($file2->getPathname());
        $this->assertEquals($expected2, file_get_contents($file2->getPathname()));
    }

    protected function getSampleDataSimple()
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

    protected function getSampleDataMulti()
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
}
