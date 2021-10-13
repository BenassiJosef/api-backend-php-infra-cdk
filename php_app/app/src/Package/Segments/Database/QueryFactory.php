<?php


namespace App\Package\Segments\Database;

use App\Models\Organization;
use App\Package\Segments\Database\BaseQueries\BaseQueryFactory;
use App\Package\Segments\Database\BaseQueries\OrganizationRegistrationBaseQuery;
use App\Package\Segments\Database\Parse\LogicParser;
use App\Package\Segments\Database\Parse\Context;
use App\Package\Segments\Fields\Field;
use App\Package\Segments\Segment;
use Doctrine\ORM\EntityManager;

/**
 * Class QueryFactory
 * @package App\Package\Segments\Database
 */
class QueryFactory
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var BaseQueryFactory $baseQueryFactory
     */
    private $baseQueryFactory;

    /**
     * QueryFactory constructor.
     * @param $entityManager
     * @param BaseQueryFactory | null $baseQueryFactory
     */
    public function __construct(
        $entityManager,
        ?BaseQueryFactory $baseQueryFactory = null
    ) {
        if ($baseQueryFactory === null) {
            $baseQueryFactory = new BaseQueryFactory();
        }
        $this->entityManager    = $entityManager;
        $this->baseQueryFactory = $baseQueryFactory;
    }

    /**
     * @param Organization $organization
     * @param Segment $segment
     * @param Field[] $baseFields
     * @return Query
     * @throws BaseQueries\Exceptions\UnknownBaseQueryException
     */
    public function make(Organization $organization, Segment $segment, array $baseFields = []): Query
    {
        return new Query(
            $segment,
            $this->baseQueryFactory->make($segment->getBaseQueryType(), $organization),
            $this->entityManager,
            Context::MODE_ALL,
            $baseFields
        );
    }

    /**
     * @param Organization $organization
     * @param Segment $segment
     * @param Field[] $baseFields
     * @return Query
     * @throws BaseQueries\Exceptions\UnknownBaseQueryException
     */
    public function makeForSMS(Organization $organization, Segment $segment, array $baseFields = []): Query
    {
        return new Query(
            $segment,
            $this->baseQueryFactory->make($segment->getBaseQueryType(), $organization),
            $this->entityManager,
            Context::MODE_SMS,
            $baseFields
        );
    }

    /**
     * @param Organization $organization
     * @param Segment $segment
     * @param Field[] $baseFields
     * @return Query
     * @throws BaseQueries\Exceptions\UnknownBaseQueryException
     */
    public function makeForEmail(Organization $organization, Segment $segment, array $baseFields = []): Query
    {
        return new Query(
            $segment,
            $this->baseQueryFactory->make($segment->getBaseQueryType(), $organization),
            $this->entityManager,
            Context::MODE_EMAIL,
            $baseFields
        );
    }

}