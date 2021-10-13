<?php


namespace App\Package\Segments;

use App\Models\Organization;
use App\Models\Segments\PersistentSegment;
use App\Package\Response\PaginatableRepository;
use App\Package\Segments\Database\Parse\Context;
use App\Package\Segments\Database\Query;
use App\Package\Segments\Database\QueryFactory;
use App\Package\Segments\Exceptions\PersistentSegmentNotFoundException;
use App\Package\Segments\Fields\Field;
use App\Package\Segments\Operators\Comparisons\ComparisonInput;
use App\Package\Segments\Operators\Logic\Container;
use App\Package\Segments\Operators\Logic\LogicalOperator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use App\Package\Segments\Operators\Comparisons\Comparison;
use App\Package\Segments\Operators\Comparisons\ComparisonFactory;
use App\Package\Segments\Operators\Logic\LogicInput;
use DateTime;

class SegmentRepository implements PaginatableRepository
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var QueryFactory $queryFactory
	 */
	private $queryFactory;

	/**
	 * @var Organization $organization
	 */
	private $organization;

	/**
	 * SegmentRepository constructor.
	 * @param EntityManager $entityManager
	 * @param QueryFactory $queryFactory
	 * @param Organization $organization
	 */
	public function __construct(
		EntityManager $entityManager,
		QueryFactory  $queryFactory,
		Organization  $organization
	) {
		$this->entityManager = $entityManager;
		$this->queryFactory  = $queryFactory;
		$this->organization  = $organization;
	}

	/**
	 * @param PersistentSegmentInput $input
	 * @return PersistentSegment
	 * @throws Database\BaseQueries\Exceptions\UnknownBaseQueryException
	 * @throws Database\Joins\Exceptions\ClassNotInPoolException
	 * @throws Database\Joins\Exceptions\InvalidClassException
	 * @throws Database\Joins\Exceptions\InvalidPropertyException
	 * @throws Database\Parse\Exceptions\InvalidQueryModeException
	 * @throws Database\Parse\Exceptions\UnsupportedNodeTypeException
	 * @throws Exceptions\InvalidReachInputException
	 * @throws Fields\Exceptions\FieldNotFoundException
	 * @throws Fields\Exceptions\InvalidClassException
	 * @throws Fields\Exceptions\InvalidPropertiesException
	 * @throws Fields\Exceptions\InvalidPropertyAliasException
	 * @throws Fields\Exceptions\InvalidTypeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonModeException
	 * @throws Operators\Comparisons\Exceptions\InvalidModifierException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorForTypeException
	 * @throws Operators\Logic\Exceptions\InvalidContainerAccessException
	 * @throws Operators\Logic\Exceptions\InvalidLogicalOperatorException
	 * @throws Values\Arguments\Exceptions\InvalidBooleanException
	 * @throws Values\Arguments\Exceptions\InvalidIntegerException
	 * @throws Values\Arguments\Exceptions\InvalidStringException
	 * @throws Values\DateTime\InvalidDateTimeException
	 * @throws Values\YearDate\Exceptions\InvalidDayException
	 * @throws Values\YearDate\Exceptions\InvalidMonthException
	 * @throws Values\YearDate\Exceptions\InvalidWeekStartDayException
	 * @throws Values\YearDate\Exceptions\InvalidYearDateFormatException
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 * @throws Exception
	 */
	public function create(PersistentSegmentInput $input): PersistentSegment
	{
		$segment = $this->segmentFromInput($input);
		return $this->persistSegment(
			new PersistentSegment(
				$this->organization,
				$input->getName(),
				$segment,
				$this->reachFromSegment($segment)
			)
		);
	}

	/**
	 * @param PersistentSegmentInput $input
	 * @return Segment|null
	 * @throws Fields\Exceptions\FieldNotFoundException
	 * @throws Fields\Exceptions\InvalidClassException
	 * @throws Fields\Exceptions\InvalidPropertiesException
	 * @throws Fields\Exceptions\InvalidPropertyAliasException
	 * @throws Fields\Exceptions\InvalidTypeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonModeException
	 * @throws Operators\Comparisons\Exceptions\InvalidModifierException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorForTypeException
	 * @throws Operators\Logic\Exceptions\InvalidLogicalOperatorException
	 * @throws Values\Arguments\Exceptions\InvalidBooleanException
	 * @throws Values\Arguments\Exceptions\InvalidIntegerException
	 * @throws Values\Arguments\Exceptions\InvalidStringException
	 * @throws Values\DateTime\InvalidDateTimeException
	 * @throws Values\YearDate\Exceptions\InvalidDayException
	 * @throws Values\YearDate\Exceptions\InvalidMonthException
	 * @throws Values\YearDate\Exceptions\InvalidWeekStartDayException
	 * @throws Values\YearDate\Exceptions\InvalidYearDateFormatException
	 */
	private function segmentFromInput(PersistentSegmentInput $input): ?Segment
	{
		if ($input->getSegment() === null) {
			return null;
		}
		return SegmentFactory::make($input->getSegment());
	}

	/**
	 * @param UuidInterface $id
	 * @param PersistentSegmentInput $input
	 * @return PersistentSegment
	 * @throws Database\BaseQueries\Exceptions\UnknownBaseQueryException
	 * @throws Database\Joins\Exceptions\ClassNotInPoolException
	 * @throws Database\Joins\Exceptions\InvalidClassException
	 * @throws Database\Joins\Exceptions\InvalidPropertyException
	 * @throws Database\Parse\Exceptions\InvalidQueryModeException
	 * @throws Database\Parse\Exceptions\UnsupportedNodeTypeException
	 * @throws Exceptions\InvalidReachInputException
	 * @throws Exceptions\InvalidSegmentInputException
	 * @throws Exceptions\SegmentException
	 * @throws Exceptions\UnknownNodeException
	 * @throws Fields\Exceptions\FieldNotFoundException
	 * @throws Fields\Exceptions\InvalidClassException
	 * @throws Fields\Exceptions\InvalidPropertiesException
	 * @throws Fields\Exceptions\InvalidPropertyAliasException
	 * @throws Fields\Exceptions\InvalidTypeException
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 * @throws ORMException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonModeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
	 * @throws Operators\Comparisons\Exceptions\InvalidModifierException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorForTypeException
	 * @throws Operators\Logic\Exceptions\InvalidContainerAccessException
	 * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
	 * @throws Operators\Logic\Exceptions\InvalidLogicalOperatorException
	 * @throws OptimisticLockException
	 * @throws PersistentSegmentNotFoundException
	 * @throws Values\Arguments\Exceptions\InvalidBooleanException
	 * @throws Values\Arguments\Exceptions\InvalidIntegerException
	 * @throws Values\Arguments\Exceptions\InvalidStringException
	 * @throws Values\DateTime\InvalidDateTimeException
	 * @throws Values\YearDate\Exceptions\InvalidDayException
	 * @throws Values\YearDate\Exceptions\InvalidMonthException
	 * @throws Values\YearDate\Exceptions\InvalidWeekStartDayException
	 * @throws Values\YearDate\Exceptions\InvalidYearDateFormatException
	 */
	public function update(UuidInterface $id, PersistentSegmentInput $input): PersistentSegment
	{
		$persistentSegment = $this->fetchSegment($id, $input->getVersion());
		$segment           = $this->segmentFromInput($input);
		$persistentSegment = $persistentSegment
			->setName($input->getName() ?? $persistentSegment->getName())
			->setSegment($segment ?? $persistentSegment->getSegment())
			->setReach($this->reachFromSegment($persistentSegment->getSegment()));
		return $this->persistSegment($persistentSegment);
	}



	/**
	 * @param UuidInterface $id
	 * @param string $mode
	 * @param Field[] $baseFields
	 * @return Query
	 * @throws Database\BaseQueries\Exceptions\UnknownBaseQueryException
	 * @throws Exceptions\InvalidSegmentInputException
	 * @throws Exceptions\UnknownNodeException
	 * @throws Fields\Exceptions\FieldNotFoundException
	 * @throws Fields\Exceptions\InvalidClassException
	 * @throws Fields\Exceptions\InvalidPropertiesException
	 * @throws Fields\Exceptions\InvalidPropertyAliasException
	 * @throws Fields\Exceptions\InvalidTypeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonModeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
	 * @throws Operators\Comparisons\Exceptions\InvalidModifierException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorForTypeException
	 * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
	 * @throws Operators\Logic\Exceptions\InvalidLogicalOperatorException
	 * @throws PersistentSegmentNotFoundException
	 * @throws Values\Arguments\Exceptions\InvalidBooleanException
	 * @throws Values\Arguments\Exceptions\InvalidIntegerException
	 * @throws Values\Arguments\Exceptions\InvalidStringException
	 * @throws Values\DateTime\InvalidDateTimeException
	 * @throws Values\YearDate\Exceptions\InvalidDayException
	 * @throws Values\YearDate\Exceptions\InvalidMonthException
	 * @throws Values\YearDate\Exceptions\InvalidWeekStartDayException
	 * @throws Values\YearDate\Exceptions\InvalidYearDateFormatException
	 */
	public function query(
		UuidInterface $id,
		string $mode = Context::MODE_ALL,
		array $baseFields = [],
		string $automatedDate = ''
	): Query {
		$queryFactory = $this->queryFactory;
		$organization = $this->organization;

		if ($id->toString() === Uuid::NIL) {
			$segment = Segment::fromArray(['root' => null]);
		} else {
			$segment      = $this->fetchSingle($id)->getSegment();
		}

		if ($automatedDate) {
			$segmentAsArray = json_decode(json_encode($segment), JSON_OBJECT_AS_ARRAY);

			$type = $segment->getRootAsContainer()->getType();
			$comparison = [
				'comparison' => ">",
				'field' => "lastInteractedAt",
				'value' => $automatedDate
			];
			$segArray = $comparison;

			if ($type === Container::TYPE_NULL) {
				$segArray = $comparison;
			}
			if ($type === Container::TYPE_LOGIC) {
				$segArray = [
					'operator' => 'and',
					'nodes' => [
						$comparison,
						$segmentAsArray['root']
					]
				];
			}

			if ($type === Container::TYPE_COMPARISON) {
				$segArray = [
					'operator' => 'and',
					'nodes' => [
						$comparison,
						$segmentAsArray['root']
					]
				];
			}

			if ($type === Container::TYPE_MODIFIED_COMPARISON) {
				$segArray = [
					'operator' => 'and',
					'nodes' => [
						$comparison,
						$segmentAsArray['root']
					]
				];
			}

			$segment = Segment::fromArray([
				'root' => $segArray
			]);
		}

		switch ($mode) {
			case Context::MODE_EMAIL:
				return $queryFactory->makeForEmail(
					$organization,
					$segment,
					$baseFields
				);
			case Context::MODE_SMS:
				return $queryFactory->makeForSMS(
					$organization,
					$segment,
					$baseFields
				);
			case Context::MODE_ALL:
			default:
				$queryFactory->make(
					$organization,
					$segment,
					$baseFields
				);
		}
		return $queryFactory->make(
			$organization,
			$segment
		);
	}

	/**
	 * @param UuidInterface $id
	 * @return PersistentSegment
	 * @throws PersistentSegmentNotFoundException
	 */
	public function fetchSingle(UuidInterface $id): PersistentSegment
	{
		return $this->fetchSegment($id);
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 * @param array $query
	 * @return PersistentSegment[]
	 */
	public function fetchAll(int $offset = 0, int $limit = 25, array $query = []): array
	{
		return $this
			->entityManager
			->getRepository(PersistentSegment::class)
			->findBy(
				$this->baseCriteria(),
				[
					'updatedAt' => 'DESC',
				],
				$limit,
				$offset
			);
	}

	/**
	 * @param array $query
	 * @return int
	 */
	public function count(array $query = []): int
	{
		return $this
			->entityManager
			->getRepository(PersistentSegment::class)
			->count(
				$this->baseCriteria()
			);
	}

	/**
	 * @return array
	 */
	private function baseCriteria(): array
	{
		return [
			'organizationId' => $this->organization->getId(),
			'deletedAt'      => null,
		];
	}

	/**
	 * @param UuidInterface $id
	 * @param UuidInterface $version
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws PersistentSegmentNotFoundException
	 */
	public function delete(UuidInterface $id, UuidInterface $version): void
	{
		$persistentSegment = $this
			->fetchSegment($id, $version);
		$persistentSegment->delete();
		$this->persistSegment($persistentSegment);
	}

	/**
	 * @param UuidInterface $id
	 * @return Reach
	 * @throws Database\BaseQueries\Exceptions\UnknownBaseQueryException
	 * @throws Database\Joins\Exceptions\ClassNotInPoolException
	 * @throws Database\Joins\Exceptions\InvalidClassException
	 * @throws Database\Joins\Exceptions\InvalidPropertyException
	 * @throws Database\Parse\Exceptions\InvalidQueryModeException
	 * @throws Database\Parse\Exceptions\UnsupportedNodeTypeException
	 * @throws Exceptions\InvalidReachInputException
	 * @throws Exceptions\InvalidSegmentInputException
	 * @throws Exceptions\UnknownNodeException
	 * @throws Fields\Exceptions\FieldNotFoundException
	 * @throws Fields\Exceptions\InvalidClassException
	 * @throws Fields\Exceptions\InvalidPropertiesException
	 * @throws Fields\Exceptions\InvalidPropertyAliasException
	 * @throws Fields\Exceptions\InvalidTypeException
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 * @throws ORMException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonModeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
	 * @throws Operators\Comparisons\Exceptions\InvalidModifierException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorForTypeException
	 * @throws Operators\Logic\Exceptions\InvalidContainerAccessException
	 * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
	 * @throws Operators\Logic\Exceptions\InvalidLogicalOperatorException
	 * @throws OptimisticLockException
	 * @throws PersistentSegmentNotFoundException
	 * @throws Values\Arguments\Exceptions\InvalidBooleanException
	 * @throws Values\Arguments\Exceptions\InvalidIntegerException
	 * @throws Values\Arguments\Exceptions\InvalidStringException
	 * @throws Values\DateTime\InvalidDateTimeException
	 * @throws Values\YearDate\Exceptions\InvalidDayException
	 * @throws Values\YearDate\Exceptions\InvalidMonthException
	 * @throws Values\YearDate\Exceptions\InvalidWeekStartDayException
	 * @throws Values\YearDate\Exceptions\InvalidYearDateFormatException
	 * .
	 */
	public function refreshReach(UuidInterface $id): Reach
	{
		$persistentSegment = $this->fetchSegment($id);
		$reach             = $this->reachFromSegment($persistentSegment->getSegment());
		$persistentSegment->setReach($reach);
		$this->persistSegment($persistentSegment);
		return $reach;
	}

	/**
	 * @param Segment $segment
	 * @return Reach
	 * @throws Database\BaseQueries\Exceptions\UnknownBaseQueryException
	 * @throws Database\Joins\Exceptions\ClassNotInPoolException
	 * @throws Database\Joins\Exceptions\InvalidClassException
	 * @throws Database\Joins\Exceptions\InvalidPropertyException
	 * @throws Database\Parse\Exceptions\InvalidQueryModeException
	 * @throws Database\Parse\Exceptions\UnsupportedNodeTypeException
	 * @throws Exceptions\InvalidReachInputException
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 * @throws Operators\Logic\Exceptions\InvalidContainerAccessException
	 */
	private function reachFromSegment(Segment $segment): Reach
	{
		return $this
			->queryFactory
			->make($this->organization, $segment)
			->reach();
	}

	/**
	 * @param UuidInterface $id
	 * @param UuidInterface|null $version
	 * @return PersistentSegment
	 * @throws PersistentSegmentNotFoundException
	 */
	private function fetchSegment(UuidInterface $id, ?UuidInterface $version = null): PersistentSegment
	{
		$params = array_merge(
			[
				'id' => $id,
			],
			$this->baseCriteria()
		);

		if ($version !== null) {
			$params['version'] = $version;
		}

		/** @var PersistentSegment | null $persistentSegment */
		$persistentSegment = $this
			->entityManager
			->getRepository(PersistentSegment::class)
			->findOneBy($params);
		if ($persistentSegment === null) {
			throw new PersistentSegmentNotFoundException(
				$id,
				$version
			);
		}
		return $persistentSegment;
	}

	/**
	 * @param PersistentSegment $segment
	 * @return PersistentSegment
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	private function persistSegment(PersistentSegment $segment): PersistentSegment
	{
		$this->entityManager->persist($segment);
		$this->entityManager->flush();
		return $segment;
	}
}
