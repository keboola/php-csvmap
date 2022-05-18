<?php

declare(strict_types=1);

namespace Keboola\CsvMap;

use Keboola\CsvMap\Exception\BadConfigException;
use PHPUnit\Framework\TestCase;

class MapperNotAllowedPrimaryKeyValueTest extends TestCase
{
    /**
     * Primary key: [{"$oid":"5716054bee6e764c94fa85a6"}]
     * @throws \Keboola\Utils\Exception
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     */
    public function testNotAllowedPrimaryKeyValue(): void
    {
        $config = [
            '_id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'id',
                    'primaryKey' => true,
                ],
            ],
            'coord' => [
                'type' => 'table',
                'destination' => 'coord',
                'tableMapping' => [
                    'a' => 'a',
                ],
            ],
        ];

        $data = $this->getSampleData();
        $parser = new Mapper($config);

        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessage('Only scalar values are allowed in primary key.');
        $parser->parse($data);
    }

    /**
     * @return array<int, object>
     */
    protected function getSampleData(): array
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
