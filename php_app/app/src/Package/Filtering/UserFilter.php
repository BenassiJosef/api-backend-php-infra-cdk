<?php

namespace App\Package\Filtering;

use App\Models\DataSources\DataSource;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Models\MarketingEvents;
use App\Models\UserProfile;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountOutputWalker;
use Carbon\Carbon;

class UnsupportedFilterOperation extends \Exception
{
}

class UserFilter
{
    private $legacyMapping = [
        'lastupdate'  => 'lastInteractedAt',
        'connections' => 'interactions',
        'optin'       => 'opt',
        'data-source' => 'dataSourceId'
    ];

    private $fieldTableMap = [
        'email'            => 'up',
        'first'            => 'up',
        'last'             => 'up',
        'age'              => 'up',
        'birthMonth'       => 'up',
        'birthDay'         => 'up',
        'ageRange'         => 'up',
        'gender'           => 'up',
        'country'          => 'up',
        'postcode'         => 'up',
        'opt'              => 'up',
        'lastInteractedAt' => 'org',
        'createdAt'        => 'org',
        'serial'           => 'rs',
        'interactions'     => 'rs',
        'dataSourceId'     => 'rs'
    ];

    public $operatorWhitelist = [
        'is', 'contains', 'notContains',
        'startsWith', 'notStartsWith',
        'endsWith', 'notEndsWith', 'before', 'after', '=', '<=', '>=', '>',
        '<',
        '!='
    ];

    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param array $serial
     * @param string|null $filterId
     * @param string|null $campaignId
     * @return mixed
     * @throws UnsupportedFilterOperation if the filter contains unsupported columns or operators
     */
    public function getProfiles(string $orgId, array $serial, ?string $filterId, string $campaignId = null)
    {
        $filters = is_null($filterId) ? null : $this->getFilter($filterId);
        return $this->getProfileQuery($orgId, $serial, $filters, is_null($campaignId), $campaignId)->getQuery()->getArrayResult();
    }

    /**
     * @param string $orgId
     * @param array $serial
     * @param array|null $filters
     * @param bool $allowCrossite
     * @param string|null $campaignId
     * @return mixed
     * @throws UnsupportedFilterOperation
     */
    public function getProfileQuery(
        string $orgId,
        array $serial,
        array $filters = null,
        bool $allowCrossite = true,
        string $campaignId = null
    ) {

        /**
         *   IFNULL(count(wpc.visits), 0) as webvisits,
         * max(wpc.lastvisitAt) as lastonweb,
         * round(sum(ur.numberOfVisits) / IF(count(DISTINCT we.id) > 0, count(DISTINCT we.id), 1), 0) as connections,
         */

        $query = $this->em->createQueryBuilder()
                          ->select(
                              "up.id AS id,
    UNIX_TIMESTAMP(MIN(org.lastInteractedAt)) * 1000 AS time,
    SUM(rs.interactions) AS connections,
    MIN(org.createdAt) AS timestamp,
    MAX(org.lastInteractedAt) AS lastupdate,
    MIN(rs.serial) AS serial,
    GROUP_CONCAT(DISTINCT rs.serial ORDER BY rs.lastInteractedAt DESC SEPARATOR ',') AS serials,
    MIN(up.email) AS email,
    MIN(up.first) AS first,
    MIN(up.phone) AS phone,
    MIN(up.gender) AS gender,
    MIN(up.postcode) AS postcode,
    MIN(up.last) AS last,
    up.opt"
                          )
                          ->from(OrganizationRegistration::class, 'org')
                          ->leftJoin(
                              RegistrationSource::class,
                              'rs',
                              Query\Expr\Join::WITH,
                              'org.id = rs.organizationRegistrationId'
                          )
                          ->join(
                              UserProfile::class,
                              'up',
                              Query\Expr\Join::WITH,
                              'up.id = org.profileId'
                          )
                          ->where('rs.serial IN (:serials)')
                          ->andWhere('org.organizationId = :orgId');

        // if we are filtering for a campaign then we need to exclude people who have already been marketed to
        if (!is_null($campaignId)) {
            $query = $query
                ->leftJoin(MarketingEvents::class, 'me', 'WITH', 'me.campaignId = :campaignId AND me.profileId = up.id')
                ->andWhere('me.timestamp IS NULL')
                ->setParameter("campaignId", $campaignId);
        }
        $query = $query
            ->andWhere('up.email is not null')
            ->setParameter('serials', $serial)
            ->setParameter('orgId', $orgId)
            ->addOrderBy('lastupdate', 'DESC')
            ->groupBy('up.id');

        /**
         * @var QueryBuilder $query
         */
        $query =  $this->addFilter($query, $filters, $allowCrossite);

        error_log($query->getDQL());
        return $query;
    }

    public function getTotalCount(string $orgId, array $serial, $filters, bool $allowCrossite = true)
    {
        $query = $this->em->createQueryBuilder()
                          ->select(
                              '
         up.id
        '
                          )
                          ->from(OrganizationRegistration::class, 'org')
                          ->leftJoin(RegistrationSource::class, 'rs', Query\Expr\Join::WITH, 'org.id = rs.organizationRegistrationId')
                          ->join(UserProfile::class, 'up', 'WITH', 'up.id = org.profileId')
                          ->where('rs.serial IN (:serials)')
                          ->andWhere('org.organizationId = :orgId')
                          ->setParameter('orgId', $orgId)
                          ->setParameter('serials', $serial)
                          ->groupBy('up.id');

        $query = $this->addFilter($query, $filters, $allowCrossite);

        $platform = $query->getEntityManager()->getConnection()->getDatabasePlatform(); // law of demeter win

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult($platform->getSQLResultCasing('dctrn_count'), 'count');

        $count_query = $query->getQuery();

        $count_query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, CountOutputWalker::class);
        $count_query->setResultSetMapping($rsm);

        return array_sum(array_map('current', $count_query->getScalarResult()));
    }

    /**
     * @param $question The question being asked
     * @param $index The index of the question (used for creating the parameter name)
     * @param array $params The collection of params that have been created so far
     * @return string The where sub clause for this question
     * @throws UnsupportedFilterOperation If the question contains an unsupported column or operator
     */
    public function getWhereClausesForQuestion($question, $index, array &$params)
    {
        // special case for birthdays
        if ($question['question'] === 'dob') {
            $startDate = new Carbon();
            $endDate   = new Carbon();
            $value     = $question['value'];
            if ($value == 'week') {
                $startDate->startOfWeek()->add(new \DateInterval('P7D'));
                $endDate->endOfWeek()->add(new \DateInterval('P7D'));
                // special handling if we are crossing a year boundary
                // This fixes the edge case where people born in january cannot be found
                // e.g. dob(01/01/2019) <= 04/01/2020 and dob(01/01/2019) >= 27/12/2019
                // The corrected query should be
                // dob(01/01/2019) <= 04/01/2019 or dob(01/01/2019) >= 27/12/2019
                if ($endDate->year > $startDate->year) {
                    $startDate->sub(new \DateInterval('P1Y'));
                    $params["dob_l_$index"] = $startDate->format('Y-m-d');
                    $params["dob_u_$index"] = $endDate->format('Y-m-d');
                    return <<<SQLTXT
STR_TO_DATE(concat(YEAR(NOW()), '-', up.birthMonth, '-', up.birthDay), '%Y-%m-%d') >= :dob_l_$index
OR
STR_TO_DATE(concat(YEAR(NOW()), '-', up.birthMonth, '-', up.birthDay), '%Y-%m-%d') <= :dob_u_$index
SQLTXT;
                } else {
                    $params["dob_l_$index"] = $startDate->format('Y-m-d');
                    $params["dob_u_$index"] = $endDate->format('Y-m-d');
                    return <<<SQLTXT
STR_TO_DATE(concat(YEAR(NOW()), '-', up.birthMonth, '-', up.birthDay), '%Y-%m-%d') >= :dob_l_$index
AND
STR_TO_DATE(concat(YEAR(NOW()), '-', up.birthMonth, '-', up.birthDay), '%Y-%m-%d') <= :dob_u_$index
SQLTXT;
                }
            }
            if ($value == 'tomorrow') {
                $startDate->add(new \DateInterval('P1D'));
                $endDate->add(new \DateInterval('P1D'));
            }
            if ($value == 'month') {
                $startDate->setDay(1);
                $endDate->setDay(1);
                $startDate->add(new \DateInterval('P1M'))->startOfMonth();
                $endDate->add(new \DateInterval('P1M'))->endOfMonth();
            }
            $params["birthMonth_l_$index"]  = (int)($startDate->format('m'));
            $params["birthMonth__u_$index"] = (int)($endDate->format('m'));
            $params["birthDay_l_$index"]    = (int)($startDate->format('d'));
            $params["birthDay_u_$index"]    = (int)($endDate->format('d'));
            return "up.birthMonth >= :birthMonth_l_$index AND up.birthMonth <= :birthMonth__u_$index AND up.birthDay >= :birthDay_l_$index AND up.birthDay <= :birthDay_u_$index";
        } else {
            $column = $question['question'];
            if (array_key_exists($column, $this->legacyMapping)) {
                $column = $this->legacyMapping[$column];
            }
            $valueParamName = $column . '_' . $index;
            // validate the requested column exists
            if (!array_key_exists($column, $this->fieldTableMap)) {
                throw new UnsupportedFilterOperation("Unsupported question '$column'");
            }
            $table = $this->fieldTableMap[$column];

            // check the operand is supported
            $operand = $question['operand'];
            if (!in_array($operand, $this->operatorWhitelist)) {
                throw new UnsupportedFilterOperation("Unsupported operand '$operand'");
            }

            // decorate the value depending on the requested operator
            $value = $question['value'];
            if ($operand === 'is') {
                $operand = '!=';
                $value   = 'N;';
                if ($value === 'false') {
                    $operand = '=';
                    $value   = 'N;';
                }
            }

            if ($operand === 'contains') {
                $operand = 'LIKE';
                $value   = '%' . $value . '%';
            }

            if ($operand === 'notContains') {
                $operand = 'NOT LIKE';
                $value   = '%' . $value . '%';
            }

            if ($operand === 'startsWith') {
                $operand = 'LIKE';
                $value   = $value . '%';
            }

            if ($operand === 'notStartsWith') {
                $operand = 'NOT LIKE';
                $value   = $value . '%';
            }

            if ($operand === 'endsWith') {
                $operand = 'LIKE';
                $value   = '%' . $value;
            }

            if ($operand === 'notEndsWith') {
                $operand = 'NOT LIKE';
                $value   = '%' . $value;
            }

            if ($operand === 'before') {
                $operand   = '<';
                $timestamp = new \DateTime();
                $timestamp->modify('- ' . $question['value'] . ' seconds');
                $value = $timestamp;
            }

            if ($operand === 'after') {
                $operand   = '>';
                $timestamp = new \DateTime();
                $timestamp->modify('- ' . $question['value'] . ' seconds');
                $value = $timestamp;
            }

            $params[$valueParamName] = $value;

            return "$table.$column  $operand  :$valueParamName";
        }
    }

    /**
     * @param $sql
     * @param $filter
     * @param $allowCrossite bool  should we also group by serial if the query contains a serial filter - this should be false for campaigns otherwise we will get duplicate profiles
     * @return mixed
     * @throws UnsupportedFilterOperation if the filter contains unsupported columns or operators
     */
    public function addFilter($sql, $filter, $allowCrossite)
    {
        $isCrossite = false;
        if (!is_null($filter)) {

            $params      = [];
            $queryString = '';

            $questions = $filter['events'];

            foreach ($questions as $key => $question) {
                $queryString     = $queryString . ' (' . $this->getWhereClausesForQuestion($question, $key, $params) . ') ';
                $isLastFilter    = count($questions) === $key + 1;
                $combineOperator = $isLastFilter ? '' : ($question['joinType'] ?? 'AND');
                $queryString     = $queryString . ' ' . $combineOperator . ' ';
            }
            $sql = $sql->andWhere($queryString);
            foreach ($params as $key => $parameter) {
                $sql = $sql->setParameter($key, $parameter);
            }
        }

        if ($isCrossite && $allowCrossite) {
            $sql = $sql->addGroupBy('ur.serial');
        }
        return $sql;
    }

    public function getFilter(string $filterEventListId): array
    {
        $filters = $this->em->createQueryBuilder()
                            ->select('u.id, u.name, u.type, u.uid, i.id as eventId, i.question, i.operand, i.value, i.joinType, i.position')
                            ->from(FilterEventList::class, 'u')
                            ->leftJoin(
                                IntegrationEventCriteria::class,
                                'i',
                                'WITH',
                                'u.id = i.filterListId'
                            )
                            ->where('u.id = :id')
                            ->setParameter('id', $filterEventListId)
                            ->getQuery()
                            ->getArrayResult();

        if (empty($filters)) {
            return [];
        }

        $result = [
            'events' => []
        ];

        $result['name']     = $filters[0]['name'];
        $result['id']       = $filters[0]['id'];
        $result['type']     = $filters[0]['type'];
        $result['readOnly'] = false;
        if (is_null($filters[0]['uid'])) {
            $result['readOnly'] = true;
        }

        foreach ($filters as $event) {
            $result['events'][] = [
                'id'       => $event['eventId'],
                'question' => $event['question'],
                'operand'  => $event['operand'],
                'value'    => $event['value'],
                'joinType' => $event['joinType'],
                'position' => $event['position']
            ];
        }

        usort(
            $result['events'], function ($a, $b) {
            return $a['position'] - $b['position'];
        }
        );

        return $result;
    }
}
