<?php


namespace App\Package\DataSources;


use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Driver\Statement as DriverStatement;

class StatementExecutor
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * StatementExecutor constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @param Statement $statement
     * @throws DBALException
     */
    public function execute(Statement $statement)
    {
        $connection = $this->entityManager->getConnection();
        $prepared   = $connection->prepare($statement->statement());
        $this->bindParameters($prepared, $statement);
        $prepared->execute();
    }

    /**
     * @param Statement[] $statements
     */
    public function executeMultiple(array $statements)
    {
        foreach ($statements as $statement) {
            $this->execute($statement);
        }
    }

    private function bindParameters(DriverStatement $driverStatement, Statement $statement)
    {
        $args = $statement->arguments();
        $keys = array_keys($args);
        foreach ($keys as $key) {
            $driverStatement->bindParam($key, $args[$key]);
        }
    }
}