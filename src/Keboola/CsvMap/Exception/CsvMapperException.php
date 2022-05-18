<?php

declare(strict_types=1);

namespace Keboola\CsvMap\Exception;

use Exception;

class CsvMapperException extends Exception
{

    /**
     * @var array<mixed> $data
     */
    protected array $data = [];

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<mixed> $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
