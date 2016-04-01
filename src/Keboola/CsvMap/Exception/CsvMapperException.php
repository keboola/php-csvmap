<?php
namespace Keboola\CsvMap\Exception;

class CsvMapperException extends \Exception
{

    protected $data = array();

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }
}
