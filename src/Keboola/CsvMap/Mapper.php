<?php

namespace Keboola\CsvMap;

use Keboola\CsvTable\Table;
use Keboola\Csv\Exception as CsvException;
use Keboola\CsvMap\Exception\BadConfigException;
use Keboola\CsvMap\Exception\BadDataException;

class Mapper
{
    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var Table
     */
    protected $result;

    /**
     * @var Mapper[]
     */
    protected $parsers = [];

    /**
     * @var array
     */
    protected $parentKey;

    /**
     * @var string
     */
    protected $parentPK;

    public function __construct(array $mapping, $type = 'root')
    {
        $this->mapping = $mapping;
        $this->type = $type;

        $this->expandShorthandDefinitions();
    }

    /**
     * Expands shorthand definitions to theirs full definition
     */
    private function expandShorthandDefinitions()
    {
        foreach ($this->mapping as $key => $settings) {
            if (is_string($key) && is_string($settings)) {
                $this->mapping[$key] = [
                    'type' => 'column',
                    'mapping' => [
                        'destination' => $settings
                    ]
                ];
            }
        }
    }

    /**
     * @param array $data
     */
    public function parse(array $data, array $userData = [])
    {
        $file = $this->getResultFile();
        foreach ($data as $row) {
            $parsedRow = $this->parseRow($row, $userData);

            try {
                $file->writeRow($parsedRow);
            } catch (CsvException $e) {
                if ($e->getCode() != 3) {
                    throw $e;
                }

                $columns = [];
                foreach ($parsedRow as $key => $value) {
                    if (!is_scalar($value) && !is_null($value)) {
                        $columns[$key] = gettype($value);
                    }
                }
                $badCols = join(',', array_keys($columns));

                $exception = new BadDataException("Error writing '{$badCols}' column: " . $e->getMessage(), 0, $e);
                $exception->setData(['bad_columns' => $columns]);
                throw $exception;
            }
        }
    }

    protected function parseRow($row, array $userData)
    {
        $result = [];
        foreach ($this->mapping as $key => $settings) {
            $delimiter = empty($settings['delimiter']) ? '.' : $settings['delimiter'];
            $propertyValue = \Keboola\Utils\getDataFromPath($key, $row, $delimiter);
            if (empty($settings['type'])) {
                $settings['type'] = 'column';
            }
            switch ($settings['type']) {
                case 'table':
                    $tableParser = $this->getParser($settings, $key);

                    if (empty($this->getPrimaryKey()) && empty($propertyValue)) {
                        if (empty($settings['parentKey']['disable'])) {
                            $result[$settings['destination']] = null;
                        }
                        break;
                    }

                    $primaryKeyValue = $this->getPrimaryKeyValues($row, $userData);
                    $this->checkPrimaryKeyValues($primaryKeyValue);

                    if (empty($settings['parentKey']['disable'])) {
                        if (empty($this->getPrimaryKey())) {
                            $result[$settings['destination']] = join(',', $primaryKeyValue);
                        }
                        $parentKeyCol = empty($settings['parentKey']['destination'])
                            ? $this->type . '_pk'
                            : $settings['parentKey']['destination'];

                        $tableParser->setParentKey($primaryKeyValue, $parentKeyCol);
                        if (!empty($settings['parentKey']['primaryKey'])) {
                            $tableParser->addParentPK($parentKeyCol);
                        }
                    }

                    // If propertyValue != array, wrap it
                    if (!is_array($propertyValue)) {
                        $propertyValue = [$propertyValue];
                    }
                    $tableParser->parse($propertyValue, $userData);

                    break;
                case 'user':
                    $result[$settings['mapping']['destination']] = \Keboola\Utils\getDataFromPath($key, $userData);
                    break;
                case 'column':
                default:
                    if (!is_scalar($propertyValue) && !is_null($propertyValue) && !empty($settings['forceType'])) {
                        $propertyValue = json_encode($propertyValue);
                    }

                    $result[$settings['mapping']['destination']] = $propertyValue;
                    break;
            }
        }
        if (!empty($this->parentKey)) {
            $result += $this->parentKey;
        }

        return $result;
    }

    private function checkPrimaryKeyValues(array $values)
    {
        foreach ($values as $value) {
            if (!is_scalar($value) && !is_null($value)) {
                throw new BadConfigException(
                    'Only scalar values are allowed in primary key. Primary key: ' . json_encode($values)
                );
            }
        }
    }

    public function getPrimaryKey()
    {
        $primaryKey = [];
        foreach ($this->mapping as $path => $settings) {
            if (!empty($settings['mapping']['primaryKey'])) {
                $primaryKey[$path] = $settings['mapping']['destination'];
            }
        }
        if (!empty($this->parentPK)) {
            $primaryKey[$this->parentPK] = $this->parentPK;
        }

        return $primaryKey;
    }

    protected function getPrimaryKeyValues($row, array $userData)
    {
        $values = [];
        if (empty($this->getPrimaryKey())) {
            if (empty($userData)) {
                $values[] = md5(serialize($row));
            } else {
                $values[] = md5(serialize($row) . serialize($userData));
            }
        } else {
            foreach ($this->getPrimaryKey() as $path => $column) {
                if (!empty($this->parentKey) && $column == key($this->parentKey)) {
                    $values[] = $this->parentKey[$column];
                } elseif (!empty($this->mapping[$path]['type']) && $this->mapping[$path]['type'] == 'user') {
                    $values[] = $userData[$path];
                } else {
                    $delimiter = empty($this->mapping[$path]['delimiter']) ? '.' : $this->mapping[$path]['delimiter'];
                    $values[] = \Keboola\Utils\getDataFromPath($path, $row, $delimiter);
                }
            }
        }

        return $values;
    }

    public function setParentKey(array $keys, $colName)
    {
        $this->parentKey = [$colName => join(',', $keys)];
    }

    /**
     * Add parent identifier to PK
     */
    public function addParentPK($colName)
    {
        $this->parentPK = $colName;
    }

    /**
     * @return static
     */
    protected function getParser(array $settings, $key)
    {
        if (!empty($settings['destination']) && $settings['destination'] == $this->type) {
            if (empty($settings['parentKey']['disable'])) {
                throw new BadConfigException(
                    "'parentKey.disable' must be true to parse child values into parent's table"
                );
            }

            return $this;
        }

        foreach (['tableMapping', 'destination'] as $requiredKey) {
            if (empty($settings[$requiredKey])) {
                throw new BadConfigException("Key '{$requiredKey}' is not set for table '{$key}'.");
            }
        }

        if (empty($this->parsers[$settings['destination']])) {
            $this->parsers[$settings['destination']] = new static($settings['tableMapping'], $settings['destination']);
        }
        return $this->parsers[$settings['destination']];
    }

    protected function getResultFile()
    {
        if (empty($this->result)) {
            $this->result = new Table($this->type, $this->getHeader());
            $this->result->setPrimaryKey(array_values($this->getPrimaryKey()));
        }

        return $this->result;
    }

    protected function getHeader()
    {
        $header = [];
        foreach ($this->mapping as $key => $settings) {
            if (empty($settings['type'])
                || $settings['type'] == 'column'
                || $settings['type'] == 'user'
            ) {
                if (empty($settings['mapping']['destination'])) {
                    throw new BadConfigException("Key 'mapping.destination' is not set for column '{$key}'.");
                }

                $header[] = $settings['mapping']['destination'];
            } elseif ($settings['type'] == 'table' && empty($this->getPrimaryKey())) {
                // TODO child table link to generate
                if (empty($settings['destination'])) {
                    throw new BadConfigException("Key 'destination' is not set for table '{$key}'.");
                }

                if (empty($settings['parentKey']['disable'])) {
                    $header[] = $settings['destination'];
                }
            }
        }
        if (!empty($this->parentKey)) {
            $header[] = key($this->parentKey);
        }
        return $header;
    }

    /**
     * Return own result and all children
     * @return Table[]
     */
    public function getCsvFiles()
    {
        $childResults = [];
        foreach ($this->parsers as $type => $parser) {
            $childResults += $parser->getCsvFiles();
        }

        $results = array_merge(
            [
                $this->type => $this->result
            ],
            $childResults
        );

        return $results;
    }
}
