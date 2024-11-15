<?php

declare(strict_types=1);

namespace Keboola\CsvMap;

use Keboola\Csv\Exception as CsvException;
use Keboola\CsvMap\Exception\BadConfigException;
use Keboola\CsvMap\Exception\BadDataException;
use Keboola\CsvTable\Table;
use function Keboola\Utils\getDataFromPath;

final class Mapper
{
    /**
     * @var array<string, array<string, mixed>|string>
     */
    protected array $mapping;

    protected bool $writeHeader;

    protected string $type;

    protected Table $result;

    /**
     * @var Mapper[]
     */
    protected array $parsers = [];

    /**
     * @var array<string, string>
     */
    protected array $parentKey;

    protected string $parentPK;

    /**
     * @param array<string, array<string, mixed>|string> $mapping
     */
    public function __construct(array $mapping, bool $writeHeader = true, string $type = 'root')
    {
        $this->mapping = $mapping;
        $this->writeHeader = $writeHeader;
        $this->type = $type;

        $this->expandShorthandDefinitions();
    }

    /**
     * Expands shorthand definitions to theirs full definition
     */
    private function expandShorthandDefinitions(): void
    {
        foreach ($this->mapping as $key => $settings) {
            if (is_string($key) && is_string($settings)) {
                $this->mapping[$key] = [
                    'type' => 'column',
                    'mapping' => [
                        'destination' => $settings,
                    ],
                ];
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>|object> $data
     * @param array<string, mixed> $userData
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\Utils\Exception
     */
    public function parse(array $data, array $userData = []): void
    {
        // Create a file, even if there is no data
        $this->getResultFile();

        foreach ($data as $row) {
            $this->parseRow($row, $userData);
        }
    }

    /**
     * @param array<string, mixed> $userData
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\Utils\Exception
     */
    public function parseRow(mixed $row, array $userData = []): void
    {
        $file = $this->getResultFile();
        $mappedRow = $this->mapRow($row, $userData);

        try {
            $file->writeRow($mappedRow);
        } catch (CsvException $e) {
            if ($e->getCode() !== 3) {
                throw $e;
            }

            $columns = [];
            foreach ($mappedRow as $key => $value) {
                if (!is_scalar($value) && !is_null($value)) {
                    $columns[$key] = gettype($value);
                }
            }
            $badCols = join(',', array_keys($columns));

            $exception = new BadDataException("Error writing '$badCols' column: " . $e->getMessage(), 0, $e);
            $exception->setData(['bad_columns' => $columns]);
            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $userData
     * @return array<int|string, mixed>
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\Utils\Exception
     */
    protected function mapRow(mixed $row, array $userData): array
    {
        $result = [];
        foreach ($this->mapping as $key => $settings) {
            $delimiter = empty($settings['delimiter']) ? '.' : (string) $settings['delimiter'];
            // Empty key means "self", ... it is useful to map scalar array values
            // Eg. row = {"actors":["Patrick Wilson","Rose Byrne","Barbara Hershey"]}
            //     mapping = {"actors: {"type": "table", "destination": "actor", "tableMapping": {"": "name:} }
            $propertyValue = is_object($row) || is_array($row) ? getDataFromPath($key, $row, $delimiter) : $row;

            if (is_array($settings)) {
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
                        $result[$settings['mapping']['destination']] = getDataFromPath($key, $userData);
                        break;
                    case 'date':
                        if ($propertyValue !== null && $time = strtotime($propertyValue)) {
                            $propertyValue = $time;
                        }

                        $result[$settings['mapping']['destination']] = $propertyValue;
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
        }
        if (!empty($this->parentKey)) {
            $result += $this->parentKey;
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $values
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    private function checkPrimaryKeyValues(array $values): void
    {
        foreach ($values as $value) {
            if (!is_scalar($value) && !is_null($value)) {
                throw new BadConfigException(
                    'Only scalar values are allowed in primary key. Primary key: ' . json_encode($values)
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getPrimaryKey(): array
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

    /**
     * @param array<string, mixed> $userData
     * @return array<int, mixed>
     * @throws \Keboola\Utils\Exception
     */
    protected function getPrimaryKeyValues(mixed $row, array $userData): array
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
                if (!empty($this->parentKey) && $column === key($this->parentKey)) {
                    $values[] = $this->parentKey[$column];
                } elseif (!empty($this->mapping[$path]['type']) && $this->mapping[$path]['type'] === 'user') {
                    $values[] = $userData[$path];
                } else {
                    $delimiter = empty($this->mapping[$path]['delimiter']) ? '.' : $this->mapping[$path]['delimiter'];
                    $values[] = getDataFromPath($path, $row, $delimiter);
                }
            }
        }

        return $values;
    }

    /**
     * @param array<int, string> $keys
     */
    public function setParentKey(array $keys, string $colName): void
    {
        $this->parentKey = [$colName => join(',', $keys)];
    }

    /**
     * Add parent identifier to PK
     */
    public function addParentPK(string $colName): void
    {
        $this->parentPK = $colName;
    }

    /**
     * @param array<string, string|mixed> $settings
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    protected function getParser(array $settings, string $key): Mapper
    {
        if (!empty($settings['destination']) && $settings['destination'] === $this->type) {
            if (empty($settings['parentKey']['disable'])) {
                throw new BadConfigException(
                    "'parentKey.disable' must be true to parse child values into parent's table"
                );
            }

            return $this;
        }

        foreach (['tableMapping', 'destination'] as $requiredKey) {
            if (empty($settings[$requiredKey])) {
                throw new BadConfigException("Key '$requiredKey' is not set for table '$key'.");
            }
        }

        if (empty($this->parsers[$settings['destination']])) {
            $this->parsers[$settings['destination']] =
                new self($settings['tableMapping'], $this->writeHeader, $settings['destination']);
        }
        return $this->parsers[$settings['destination']];
    }

    /**
     * @throws \Keboola\Csv\InvalidArgumentException
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    protected function getResultFile(): Table
    {
        if (empty($this->result)) {
            $this->result = new Table($this->type, $this->getHeader(), $this->writeHeader);
            $this->result->setPrimaryKey(array_values($this->getPrimaryKey()));
        }

        return $this->result;
    }

    /**
     * @return array<int, mixed>
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    protected function getHeader(): array
    {
        $header = [];
        foreach ($this->mapping as $key => $settings) {
            if (empty($settings['type'])
                || $settings['type'] === 'column'
                || $settings['type'] === 'user'
                || $settings['type'] === 'date'
            ) {
                if (empty($settings['mapping']['destination'])) {
                    throw new BadConfigException("Key 'mapping.destination' is not set for column '$key'.");
                }

                $header[] = $settings['mapping']['destination'];
            } elseif ($settings['type'] === 'table' && empty($this->getPrimaryKey())) {
                // TODO child table link to generate
                if (empty($settings['destination'])) {
                    throw new BadConfigException("Key 'destination' is not set for table '$key'.");
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
     * @return array<string, Table>
     * @throws \Keboola\Csv\InvalidArgumentException
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\CsvMap\Exception\BadConfigException
     */
    public function getCsvFiles(): array
    {
        $childResults = [];
        foreach ($this->parsers as $parser) {
            $childResults += $parser->getCsvFiles();
        }

        return array_merge(
            [
                $this->type => $this->getResultFile(),
            ],
            $childResults
        );
    }
}
