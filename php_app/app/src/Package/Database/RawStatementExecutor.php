<?php


namespace App\Package\Database;

use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement as DoctrineStatement;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Slim\Http\StatusCode;

/**
 * Class RawStatementExecutor
 * @package App\Package\Database
 */
class RawStatementExecutor implements Database
{
    /**
     * @param array $parameters
     * @throws UnsupportedParamTypeException
     */
    public static function validateParameters(array $parameters): void
    {
        foreach ($parameters as $key => $parameter) {
            if (!array_key_exists(gettype($parameter), self::$typeMap)) {
                throw new UnsupportedParamTypeException($parameter, $key);
            }
        }
    }

    /**
     * @param mixed[] $parameters
     * @return string[]
     * @throws UnsupportedParamTypeException
     */
    private static function mapParametersToTypes(array $parameters): array
    {
        $mapping = self::$typeMap;
        return from($parameters)
            ->select(
                function ($param, string $key) use ($mapping): string {
                    $type = gettype($param);
                    if (!array_key_exists($type, $mapping)) {
                        throw new UnsupportedParamTypeException($param, $key);
                    }
                    return $mapping[$type];
                },
                function ($param, string $key): string {
                    return $key;
                }
            )
            ->toArray();
    }

    /**
     * @var int[] $typeMap
     */
    private static $typeMap = [
        'string'  => ParameterType::STRING,
        'double'  => ParameterType::STRING,
        'integer' => ParameterType::INTEGER,
        'boolean' => ParameterType::BOOLEAN,
        'NULL'    => ParameterType::NULL,
    ];

    /**
     * @return int[]
     */
    public static function pdoTypeMapping(): array
    {
        return self::$typeMap;
    }

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * RawStatementExecutor constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function beginTransaction()
    {
        $this->entityManager->beginTransaction();
    }

    public function commit()
    {
        $this->entityManager->commit();
    }

    public function rollback()
    {
        $this->entityManager->rollback();
    }

    /**
     * @param Statement $statement
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function execute(Statement $statement)
    {
        $this
            ->prepareAndExecute($statement);
    }

    /**
     * @param Statement $statement
     * @return array
     * @throws UnsupportedParamTypeException
     * @throws FailedToExecuteStatementException
     */
    public function fetchAll(Statement $statement): array
    {
        try {
            return $this
                ->prepareAndExecute($statement)
                ->fetchAllAssociative();
        } catch (DriverException $exception) {
            throw new FailedToExecuteStatementException($statement, $exception);
        }
    }

    /**
     * @param Statement $statement
     * @return array
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function fetchFirstColumn(Statement $statement): array
    {
        try {
            return $this
                ->prepareAndExecute($statement)
                ->fetchFirstColumn();
        } catch (DriverException $exception) {
            throw new FailedToExecuteStatementException($statement, $exception);
        }
    }

    /**
     * @param Statement $statement
     * @return int
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function fetchSingleIntResult(Statement $statement): int
    {
        try {
            $result = $this
                ->prepareAndExecute($statement)
                ->fetchOne();
        } catch (DriverException $exception) {
            throw new FailedToExecuteStatementException($statement, $exception);
        }
        if ($result === false) {
            throw new FailedToExecuteStatementException($statement);
        }
        return (int)$result;
    }

    /**
     * @param Statement $statement
     * @return DoctrineStatement
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    private function prepareAndExecute(Statement $statement): DoctrineStatement
    {
        $preparedStatement = $this->prepare($statement);
        $parameters        = $statement->parameters();
        $typeMap           = self::mapParametersToTypes($parameters);
        foreach ($typeMap as $key => $type) {
            $preparedStatement->bindParam($key, $parameters[$key], $type);
        }
        try {
            $success = $preparedStatement->execute();
        } catch (DriverException $exception) {
            throw new FailedToExecuteStatementException($statement, $exception);
        }
        if (!$success) {
            throw new FailedToExecuteStatementException($statement);
        }
        return $preparedStatement;
    }

    private function prepare(Statement $statement): DoctrineStatement
    {
        return $this
            ->entityManager
            ->getConnection()
            ->prepare($statement->query());
    }
}
