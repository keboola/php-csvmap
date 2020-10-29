# CsvMap

[![Build Status](https://travis-ci.org/keboola/php-csvmap.svg?branch=master)](https://travis-ci.org/keboola/php-csvmap)
[![Latest Stable Version](https://poser.pugx.org/keboola/csvmap/version)](https://packagist.org/packages/keboola/csvmap)
[![Total Downloads](https://poser.pugx.org/keboola/csvmap/downloads)](https://packagist.org/packages/keboola/csvmap)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/keboola/php-csvmap/blob/master/LICENSE.md)

## Installation

```console
composer require keboola/csvmap
```

## Usage

For example, map key `key.nested` of each `$data` array item, to CSV column `mappedKey`.

```
$data = [
    [
        'id => '123',
        'key' => [
            'nested' => 'value1'
        ]
    ] 
];
$mapping = [ 
    'id' => [
        'type' => 'column', 
        'mapping' => [
            'destination' => 'id',
            'primaryKey' => true,
        ] 
    ],
    'key.nested' => 'mappedKey' 
];

$rootType = 'rootName';
$userData = [];
$parser = new Mapper($mapping, $writeHeader, $rootType);
$parser->parse($data, $userData);

$files = $parser->getCsvFiles();
$tempFilePath = $files['rootName']->getPathName();
```

## Mapping

- The mapping is an array.
- The **`key`** corresponds to the one root/nested key in the source data. Default delimiter is `.`, eg. `key`, `key.nested`.
- The **`value`** is the mapping configuration for the given key. It is `string` (shorthand notation) or `array`.
    - There are 3 types of the mapping, defined by the `type` key:
    - `type` (optional), `column` by default.
        - `column` will store the value from its key into a CSV column
        - `user` will look into an array in the second argument of the parse function and fill a CSV column with its value
        - `table` will create a "child" CSV and link through a primary key or a hash, if no primary key is defined

## Column mapping

- `mapping`: Required, must contain `destination`:
    - `destination`: Target column in the output CSV file
    - `primaryKey`: Optional, boolean. If set to true, the column will be included in the primary key
- `forceType`: Optional, if a value is not scalar, it'll be JSON encoded

#### Shorthand notation

- If the **`value`** is the `string`, then it is a shorthand notation for the `column` mapping.
- The string value corresponds to `mapping.destination`.

Example shorthand notation:
```
[
     'key.nested' => 'mappedKey' 
]
``` 

... is equal to 
```
[
    'key.nested' => [
        'type' => 'column', 
        'mapping' => [
            'destination' => 'mappedKey'
        ] 
    ]
]
```

#### Examples
Four different `column` mappings.
```
[
    'id' => [
        'type' => 'column', 
        'mapping' => [
            'destination' => 'id',
            'primaryKey' => true,
        ] 
    ],
    'name' => 'name',
    'info.url' => 'url,
    'info.tags' => [
        'type' => 'column', 
        'mapping' => [
            'destination' => 'tags',
            'forceType' => true,
        ] 
    ]
]
```

## User mapping

Same as `column`, except the **key** of the object is not searched for in the parsed data, but in an array passed to the parser to inject user data

## Table mapping

- `destination`: Required, a target CSV file name
- `tableMapping`: Required, mapping of all child table's columns
    - Sub-mapping has the same structure as the root `$mapping`.
- `parentKey`: Optional, can be used to set the parent/child link as a primary key in the child or override the link's column name in the child
    - `primaryKey`: boolean, same as in `column`
    - `destination`: Name of the link column (if not used, name of the parent table . `_pk` is used by default)
    - `disable`: boolean, if set to non-false value, the parent key in the child table, as well as the column in the parent will not be saved

*Note:*  
If the `destination` is the same as the current parsed 'type' (destination of the parent),   
`parentKey.disable` **must** be true to preserve consistency of structure of the child and parent.


#### Map scalar items to a separated CSV

- Table mapping is useful when you need to map array of the **objects** to separate CSV tables.
- But sometimes you need to map an array of the **scalar** (not object) values, for example a list of tags.
- In this case, you can use an **empty key** in `tableMapping` to map a scalar value.

For example, we have this data:
```
[
    ['id' => 1, 'name' => 'dog', 'tags' => ['useful', 'pet', 'animal']]
    ['id' => 2, 'name' => 'mouse', 'tags' => ['harmful', 'animal']],
]
```

Example mapping:
```
[
    'id' => [
        'type' => 'column', 
        'mapping' => [
            'destination' => 'id',
            'primaryKey' => true,
        ]
    ],
    'name' => 'name',
    'tags' => [
        'type' => 'table',
        'destination' => 'tags',
        'tableMapping' => [
            '' => 'tagName' // empty key used to map scalar value
        ]
    ]
]
```

Results:

`root.csv`:
```csv
"id","name"
"1","dog"
"2","mouse"
```

`tags.csv`:
```csv
"tagName","root_pk"
"useful","1"
"pet","1"
"animal","1"
"harmful","2"
"animal","2"
```


#### Examples

Mixed `column` and `table` mappings.
```
[
    'id' => [
        'type' => 'column', 
        'mapping' => [
            'destination' => 'id',
            'primaryKey' => true,
        ]
    ],
    'name' => "name,
    'addresses' => [
        'type' => 'table', 
        'destination' => 'addresses',
        'tableMapping' => [
            'number' => 'number',
            'street' => [
                'type' => 'table',
                'destination' => 'streets',
                'tableMapping' => [
                    'name' => 'name'
                ]        
            ]
         ]
    ]
]
```
