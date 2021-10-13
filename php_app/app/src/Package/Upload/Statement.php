<?php


namespace App\Package\Upload;


use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;
use JsonSerializable;

/**
 * Class Statement
 * @package App\Package\Upload
 */
class Statement implements JsonSerializable
{
    /**
     * @var string $statement
     */
    private $statement;

    /**
     * @var array $parameters
     */
    private $parameters;

    /**
     * Statement constructor.
     * @param string $statement
     * @param array $parameters
     */
    public function __construct(string $statement, array $parameters)
    {
        $this->statement  = $statement;
        $this->parameters = $parameters;
    }

    /**
     * @param DriverStatement $statement
     */
    public function bindParameters(DriverStatement $statement)
    {
        foreach ($this->parameters as $key => $parameter) {
            $statement->bindParam($key, $this->parameters[$key]);
        }
    }

    /**
     * @return string
     */
    public function getStatement(): string
    {
        return $this->statement;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'statement'  => $this->getStatement(),
            'parameters' => $this->getParameters(),
        ];
    }
}