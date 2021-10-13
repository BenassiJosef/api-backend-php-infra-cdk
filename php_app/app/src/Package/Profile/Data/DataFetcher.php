<?php

namespace App\Package\Profile\Data;

use App\Package\Database\Database;
use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;
use App\Package\Database\RawStatementExecutor;
use App\Package\Profile\Data\Presentation\DataObject;
use App\Package\Profile\Data\Presentation\Section;
use Doctrine\ORM\EntityManager;

/**
 * Class DataFetcher
 * @package App\Package\Profile\Data
 */
class DataFetcher
{
    /**
     * @var Database $database
     */
    private $database;

    /**
     * @var DataDefinition $dataDefinition
     */
    private $dataDefinition;

    /**
     * DataFetcher constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->database       = new RawStatementExecutor($entityManager);
        $this->dataDefinition = DataDefinition::base();
    }

    /**
     * @param Subject $subject
     * @return Section[]
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function dataForSubject(Subject $subject): array
    {
        $sections = [];
        foreach ($this->dataDefinition->areas() as $area) {
            $section = new Section($area->name());
            foreach ($area->selectable() as $definition) {
                $section->add(
                    $this->dataObject($definition, $subject)
                );
            }
            $sections[] = $section;
        }
        return from($sections)
            ->where(
                function (Section $section): bool {
                    return $section->rowCount() > 0;
                }
            )
            ->toArray();
    }

    /**
     * @param Selectable $definition
     * @param Subject $subject
     * @return DataObject
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    private function dataObject(Selectable $definition, Subject $subject): DataObject
    {
        return new DataObject(
            $definition,
            $this
                ->database
                ->fetchAll(
                    $definition->select(
                        $subject
                    ),
                )
        );
    }
}