<?php

namespace Keboola\CsvMap;

class MapperNotAllowedPrimaryKeyValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Only scalar values are allowed in primary key.
     * Primary key: [{"$oid":"5716054bee6e764c94fa85a6"}]
     */
    public function testNotAllowedPrimaryKeyValue()
    {
        $config = [
            '_id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'id',
                    'primaryKey' => true,
                ]
            ],
            'coord' => [
                'type' => 'table',
                'destination' => 'coord',
                'tableMapping' => [
                    'a' => 'a',
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    protected function getSampleData()
    {
        $json = <<<JSON
[
  {
    "_id": {
      "\$oid": "5716054bee6e764c94fa85a6"
    },
    "coord": [
      {
        "a": 1
      },
      {
        "a": 2
      }
    ]
  }
]
JSON;
        return json_decode($json);
    }
}
