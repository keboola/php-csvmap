<?php

namespace Keboola\CsvMap;

use Keboola\CsvMap\Exception\BadConfigException;
use PHPUnit\Framework\TestCase;

class MapperNotAllowedPrimaryKeyValueTest extends TestCase
{
    /**
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

        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessage('Only scalar values are allowed in primary key.');
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
