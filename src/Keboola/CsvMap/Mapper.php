<?php

namespace Keboola\CsvMap;

use Keboola\CsvTable\Table;
use Keboola\Utils\Utils;
use Keboola\CsvMap\Exception\BadConfigException;

/**
 *
 */
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

    public function __construct(array $mapping, $type = 'root')
    {
        $this->mapping = $mapping;
        $this->type = $type;
    }

    /**
     * @param array $data
     */
    public function parse(array $data, array $userData = [])
    {
        $file = $this->getResultFile();
        foreach($data as $row) {
            $parsedRow = $this->parseRow($row, $userData);
            $file->writeRow($parsedRow);
        }
    }

    protected function parseRow($row, array $userData)
    {
        $result = [];
        foreach($this->mapping as $key => $settings) {
            $delimiter = empty($settings['delimiter']) ? '.' : $settings['delimiter'];
            $propertyValue = Utils::getDataFromPath($key, $row, $delimiter);
            if(empty($settings['type'])) {
                $settings['type'] = 'column';
            }
            switch ($settings['type']) {
                case 'table':
                    if (empty($propertyValue)) {
                        if (empty($this->getPrimaryKey())) {
                            $result[$settings['destination']] = null;
                        }
                        break;
                    }

                    foreach(['tableMapping', 'destination'] as $requiredKey) {
                        if (empty($settings[$requiredKey])) {
                            throw new BadConfigException("Key '{$requiredKey}' must be set for each table.");
                        }
                    }

                    $tableParser = $this->getParser($settings['tableMapping'], $settings['destination']);
                    $tableParser->setParentKey($this->getPrimaryKeyValues($row, $userData), $this->type . '_pk');
                    if (empty($this->getPrimaryKey())) {
                        $result[$settings['destination']] = join(',', $this->getPrimaryKeyValues($row, $userData));
                    }

                    $tableParser->parse($propertyValue);
                    break;
                case 'user':
                    $result[$settings['mapping']['destination']] = Utils::getDataFromPath($key, $userData);
                    break;
                case 'column':
                default:
                    $result[$settings['mapping']['destination']] = $propertyValue;
                    break;
            }
        }
        if (!empty($this->parentKey)) {
            $result += $this->parentKey;
        }

        return $result;
    }

    public function getPrimaryKey()
    {
        $primaryKey = [];
        foreach($this->mapping as $path => $settings) {
            if (!empty($settings['mapping']['primaryKey'])) {
                $primaryKey[$path] = $settings['mapping']['destination'];
            }
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
            foreach($this->getPrimaryKey() as $path => $column) {
                if (!empty($this->mapping[$path]['type']) && $this->mapping[$path]['type'] == 'user') {
                    $values[] = $userData[$path];
                } else {
                    $delimiter = empty($this->mapping[$path]['delimiter']) ? '.' : $this->mapping[$path]['delimiter'];
                    $values[] = Utils::getDataFromPath($path, $row, $delimiter);
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
     * @return static
     */
    protected function getParser(array $mapping, $type)
    {
        if (empty($this->parsers[$type])) {
            $this->parsers[$type] = new static($mapping, $type);
        }
        return $this->parsers[$type];
    }

    protected function getResultFile()
    {
        if (empty($this->result)) {
            $this->result = Table::create($this->type, $this->getHeader());
            $this->result->setPrimaryKey(array_values($this->getPrimaryKey()));
        }

        return $this->result;
    }

    protected function getHeader()
    {
        $header = [];
        foreach($this->mapping as $settings) {
            if (
                empty($settings['type'])
                || $settings['type'] == 'column'
                || $settings['type'] == 'user'
            ) {
                if (empty($settings['mapping']['destination'])) {
                    throw new BadConfigException("Key 'mapping.destination' must be set for each column.");
                }

                $header[] = $settings['mapping']['destination'];
            } elseif ($settings['type'] == 'table' && empty($this->getPrimaryKey())) {
                // TODO child table link to generate
                if (empty($settings['destination'])) {
                    throw new BadConfigException("Key 'destination' must be set for each table.");
                }

                $header[] = $settings['destination'];
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
        foreach($this->parsers as $type => $parser) {
            $childResults[$type] = $parser->getCsvFiles()[$type];
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
