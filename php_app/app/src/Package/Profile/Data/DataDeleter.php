<?php

namespace App\Package\Profile\Data;

use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;
use App\Package\Database\Executor;
use App\Package\Profile\Data\Exceptions\DeletionException;
use App\Package\Database\Transaction;
use Throwable;

/**
 * Class DataDeleter
 * @package App\Package\Profile\Data
 */
class DataDeleter
{
    /**
     * @var Executor | Transaction $database
     */
    private $database;

    /**
     * @var Subject $subject
     */
    private $subject;

    /**
     * DataDeleter constructor.
     * @param Executor $database
     * @param Subject $subject
     */
    public function __construct(
        Executor $database,
        Subject $subject
    ) {
        $this->database = $database;
        $this->subject  = $subject;
    }

    /**
     * @param DataArea $area
     * @param ObjectDefinition $objectDefinition
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function __invoke(DataArea $area, ObjectDefinition $objectDefinition)
    {
        if (!$objectDefinition instanceof Deletable) {
            return;
        }
        $this->database->beginTransaction();
        foreach ($objectDefinition->delete($this->subject) as $statement) {
            try {
                $this
                    ->database
                    ->execute(
                        $statement
                    );
            } catch (Throwable $exception) {
                $this->database->rollback();
                throw new DeletionException($objectDefinition, $exception);
            }
        }
        $this->database->commit();
    }
}