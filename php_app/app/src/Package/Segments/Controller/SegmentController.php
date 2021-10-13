<?php


namespace App\Package\Segments\Controller;


use App\Package\Exceptions\InvalidUUIDException;
use App\Package\Organisations\Exceptions\OrganizationNotFoundException;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Pagination\RepositoryPaginatedResponse;
use App\Package\Response\PaginationResponder;
use App\Package\Segments\Database\BaseQueries\Exceptions\UnknownBaseQueryException;
use App\Package\Segments\Database\Joins\Exceptions\ClassNotInPoolException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidClassException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidPropertyException;
use App\Package\Segments\Database\Parse\Context;
use App\Package\Segments\Database\Parse\Exceptions\InvalidQueryModeException;
use App\Package\Segments\Database\Parse\Exceptions\UnsupportedLogicalOperatorException;
use App\Package\Segments\Database\Parse\Exceptions\UnsupportedNodeTypeException;
use App\Package\Segments\Database\Query;
use App\Package\Segments\Database\QueryFactory;
use App\Package\Segments\Exceptions\InvalidReachInputException;
use App\Package\Segments\Exceptions\InvalidSegmentInputException;
use App\Package\Segments\Exceptions\PersistentSegmentNotFoundException;
use App\Package\Segments\Exceptions\UnknownNodeException;
use App\Package\Segments\Fields\Exceptions\FieldNotFoundException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Fields\FieldList;
use App\Package\Segments\Metadata\Metadata;
use App\Package\Segments\Operators\Comparisons\ComparisonFactory;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidComparisonModeException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidComparisonSignatureException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidModifierException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorForTypeException;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidLogicalOperatorException;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidLogicInputSignatureException;
use App\Package\Segments\PersistentSegmentInput;
use App\Package\Segments\Segment;
use App\Package\Segments\SegmentRepositoryFactory;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidBooleanException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidIntegerException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidStringException;
use App\Package\Segments\Values\DateTime\DateTimeFactory;
use App\Package\Segments\Values\DateTime\InvalidDateTimeException;
use App\Package\Segments\Values\ValueFactory;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidWeekStartDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidYearDateFormatException;
use App\Package\Segments\Values\YearDate\YearDateRangeFactory;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class SegmentController
{
	/**
	 * @var QueryFactory $queryFactory
	 */
	private $queryFactory;

	/**
	 * @var OrganizationProvider $organizationProvider
	 */
	private $organizationProvider;

	/**
	 * @var SegmentRepositoryFactory $segmentRepositoryFactory
	 */
	private $segmentRepositoryFactory;

	/**
	 * @var PaginationResponder $paginationResponder
	 */
	private $paginationResponder;

	/**
	 * SegmentController constructor.
	 * @param EntityManager $entityManager
	 * @param QueryFactory $queryFactory
	 * @param OrganizationProvider $organizationProvider
	 */
	public function __construct(
		EntityManager $entityManager,
		QueryFactory $queryFactory,
		OrganizationProvider $organizationProvider
	) {
		$this->queryFactory             = $queryFactory;
		$this->organizationProvider     = $organizationProvider;
		$this->segmentRepositoryFactory = new SegmentRepositoryFactory(
			$entityManager,
			$queryFactory,
			$organizationProvider
		);
		$this->paginationResponder      = PaginationResponder::fromRepositoryProvider($this->segmentRepositoryFactory);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidTypeException
	 * @throws InvalidWeekStartDayException
	 * @throws \App\Package\Segments\Fields\Exceptions\InvalidClassException
	 */
	public function metadata(Request $request, Response $response): Response
	{
		$valueFactory = ValueFactory::configure(
			$request->getQueryParam('weekStart', YearDateRangeFactory::WEEK_START_MONDAY),
			$request->getQueryParam('dateFormat', DateTimeFactory::INPUT_FORMAT)
		);
		return $response->withJson(
			new Metadata(
				$valueFactory,
				ComparisonFactory::fromValueFactory($valueFactory)
			)
		);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ClassNotInPoolException
	 * @throws FieldNotFoundException
	 * @throws InvalidBooleanException
	 * @throws InvalidClassException
	 * @throws InvalidComparisonModeException
	 * @throws InvalidComparisonSignatureException
	 * @throws InvalidContainerAccessException
	 * @throws InvalidDateTimeException
	 * @throws InvalidDayException
	 * @throws InvalidIntegerException
	 * @throws InvalidLogicInputSignatureException
	 * @throws InvalidLogicalOperatorException
	 * @throws InvalidModifierException
	 * @throws InvalidMonthException
	 * @throws InvalidOperatorException
	 * @throws InvalidOperatorForTypeException
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidPropertyException
	 * @throws InvalidQueryModeException
	 * @throws InvalidReachInputException
	 * @throws InvalidSegmentInputException
	 * @throws InvalidStringException
	 * @throws InvalidTypeException
	 * @throws InvalidUUIDException
	 * @throws InvalidWeekStartDayException
	 * @throws InvalidYearDateFormatException
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 * @throws OrganizationNotFoundException
	 * @throws UnknownBaseQueryException
	 * @throws UnknownNodeException
	 * @throws UnsupportedNodeTypeException
	 * @throws \App\Package\Segments\Fields\Exceptions\InvalidClassException
	 */
	public function reach(Request $request, Response $response): Response
	{
		return $response
			->withJson(
				$this
					->getQueryFromRequest(
						$request,
						Segment::fromArray($request->getParsedBody()),
						FieldList::default()->filterByKey(
							...$request->getQueryParam('fields', [])
						)
					)
					->reach()
			);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws FieldNotFoundException
	 * @throws InvalidBooleanException
	 * @throws InvalidComparisonModeException
	 * @throws InvalidComparisonSignatureException
	 * @throws InvalidContainerAccessException
	 * @throws InvalidDateTimeException
	 * @throws InvalidDayException
	 * @throws InvalidIntegerException
	 * @throws InvalidLogicInputSignatureException
	 * @throws InvalidLogicalOperatorException
	 * @throws InvalidModifierException
	 * @throws InvalidMonthException
	 * @throws InvalidOperatorException
	 * @throws InvalidOperatorForTypeException
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidSegmentInputException
	 * @throws InvalidStringException
	 * @throws InvalidTypeException
	 * @throws InvalidUUIDException
	 * @throws InvalidWeekStartDayException
	 * @throws InvalidYearDateFormatException
	 * @throws OrganizationNotFoundException
	 * @throws UnknownBaseQueryException
	 * @throws UnknownNodeException
	 * @throws UnsupportedLogicalOperatorException
	 * @throws UnsupportedNodeTypeException
	 * @throws \App\Package\Segments\Fields\Exceptions\InvalidClassException
	 */
	public function dql(Request $request, Response $response): Response
	{

		return $response
			->withJson(
				$this
					->getQueryFromRequest(
						$request,
						Segment::fromArray($request->getParsedBody()),
						FieldList::default()->filterByKey(
							...$request->getQueryParam('fields', [])
						)
					)
					->build(
						$request->getQueryParam('offset', 0),
						$request->getQueryParam('limit', 25)
					)
					->getDQL()
			);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws FieldNotFoundException
	 * @throws InvalidBooleanException
	 * @throws InvalidComparisonModeException
	 * @throws InvalidComparisonSignatureException
	 * @throws InvalidDateTimeException
	 * @throws InvalidDayException
	 * @throws InvalidIntegerException
	 * @throws InvalidLogicInputSignatureException
	 * @throws InvalidLogicalOperatorException
	 * @throws InvalidModifierException
	 * @throws InvalidMonthException
	 * @throws InvalidOperatorException
	 * @throws InvalidOperatorForTypeException
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidSegmentInputException
	 * @throws InvalidStringException
	 * @throws InvalidTypeException
	 * @throws InvalidUUIDException
	 * @throws InvalidWeekStartDayException
	 * @throws InvalidYearDateFormatException
	 * @throws OrganizationNotFoundException
	 * @throws UnknownBaseQueryException
	 * @throws UnknownNodeException
	 * @throws \App\Package\Segments\Fields\Exceptions\InvalidClassException
	 */
	public function preview(Request $request, Response $response): Response
	{
		$allData = RepositoryPaginatedResponse::fromRequestAndRepository(
			$request,
			$this
				->getQueryFromRequest(
					$request,
					Segment::fromArray($request->getParsedBody()),
					FieldList::default()->filterByKey(
						...$request->getQueryParam('fields', [])
					)
				)
		);
		return $response->withJson($allData);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function fetchAll(Request $request, Response $response): Response
	{
		return $this
			->paginationResponder
			->respond($request, $response);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws PersistentSegmentNotFoundException
	 * @throws Exception
	 */
	public function fetch(Request $request, Response $response): Response
	{
		return $response->withJson(
			$this
				->segmentRepositoryFactory
				->segmentRepository($request)
				->fetchSingle(
					$this->idFromRequest($request)
				)
		);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ClassNotInPoolException
	 * @throws FieldNotFoundException
	 * @throws InvalidBooleanException
	 * @throws InvalidClassException
	 * @throws InvalidComparisonModeException
	 * @throws InvalidComparisonSignatureException
	 * @throws InvalidContainerAccessException
	 * @throws InvalidDateTimeException
	 * @throws InvalidDayException
	 * @throws InvalidIntegerException
	 * @throws InvalidLogicInputSignatureException
	 * @throws InvalidLogicalOperatorException
	 * @throws InvalidModifierException
	 * @throws InvalidMonthException
	 * @throws InvalidOperatorException
	 * @throws InvalidOperatorForTypeException
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidPropertyException
	 * @throws InvalidQueryModeException
	 * @throws InvalidReachInputException
	 * @throws InvalidSegmentInputException
	 * @throws InvalidStringException
	 * @throws InvalidTypeException
	 * @throws InvalidWeekStartDayException
	 * @throws InvalidYearDateFormatException
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws PersistentSegmentNotFoundException
	 * @throws UnknownBaseQueryException
	 * @throws UnknownNodeException
	 * @throws UnsupportedNodeTypeException
	 * @throws \App\Package\Segments\Fields\Exceptions\InvalidClassException
	 * @throws Exception
	 */
	public function refreshReachForPersistentSegment(Request $request, Response $response): Response
	{
		return $response->withJson(
			$this
				->segmentRepositoryFactory
				->segmentRepository($request)
				->refreshReach(
					$this->idFromRequest($request)
				)
		);
	}

	/**
	 * @param Request $request
	 * @return UuidInterface
	 */
	private function idFromRequest(Request $request): UuidInterface
	{
		$segmentId = $request->getAttribute('id', Uuid::NIL);
		if ($segmentId === 'all') {
			$segmentId = Uuid::NIL;
		}

		return Uuid::fromString($segmentId);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws PersistentSegmentNotFoundException
	 */
	public function delete(Request $request, Response $response): Response
	{
		$this
			->segmentRepositoryFactory
			->segmentRepository($request)
			->delete(
				$this->idFromRequest($request),
				$this->versionFromRequest($request)
			);
		return $response->withStatus(StatusCodes::HTTP_NO_CONTENT);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ClassNotInPoolException
	 * @throws FieldNotFoundException
	 * @throws InvalidBooleanException
	 * @throws InvalidClassException
	 * @throws InvalidComparisonModeException
	 * @throws InvalidComparisonSignatureException
	 * @throws InvalidContainerAccessException
	 * @throws InvalidDateTimeException
	 * @throws InvalidDayException
	 * @throws InvalidIntegerException
	 * @throws InvalidLogicInputSignatureException
	 * @throws InvalidLogicalOperatorException
	 * @throws InvalidModifierException
	 * @throws InvalidMonthException
	 * @throws InvalidOperatorException
	 * @throws InvalidOperatorForTypeException
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidPropertyException
	 * @throws InvalidQueryModeException
	 * @throws InvalidReachInputException
	 * @throws InvalidSegmentInputException
	 * @throws InvalidStringException
	 * @throws InvalidTypeException
	 * @throws InvalidWeekStartDayException
	 * @throws InvalidYearDateFormatException
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 * @throws UnknownBaseQueryException
	 * @throws UnknownNodeException
	 * @throws UnsupportedNodeTypeException
	 * @throws \App\Package\Segments\Fields\Exceptions\InvalidClassException
	 */
	public function create(Request $request, Response $response): Response
	{
		return $response->withJson(
			$this
				->segmentRepositoryFactory
				->segmentRepository($request)
				->create(
					PersistentSegmentInput::fromRequest($request)
				),
			StatusCodes::HTTP_CREATED
		);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws UnknownBaseQueryException
	 * @throws ClassNotInPoolException
	 * @throws InvalidClassException
	 * @throws InvalidPropertyException
	 * @throws InvalidQueryModeException
	 * @throws UnsupportedNodeTypeException
	 * @throws InvalidReachInputException
	 * @throws InvalidSegmentInputException
	 * @throws PersistentSegmentNotFoundException
	 * @throws UnknownNodeException
	 * @throws FieldNotFoundException
	 * @throws \App\Package\Segments\Fields\Exceptions\InvalidClassException
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidTypeException
	 * @throws InvalidComparisonModeException
	 * @throws InvalidComparisonSignatureException
	 * @throws InvalidModifierException
	 * @throws InvalidOperatorException
	 * @throws InvalidOperatorForTypeException
	 * @throws InvalidContainerAccessException
	 * @throws InvalidLogicInputSignatureException
	 * @throws InvalidLogicalOperatorException
	 * @throws InvalidBooleanException
	 * @throws InvalidIntegerException
	 * @throws InvalidStringException
	 * @throws InvalidDateTimeException
	 * @throws InvalidDayException
	 * @throws InvalidMonthException
	 * @throws InvalidWeekStartDayException
	 * @throws InvalidYearDateFormatException
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function update(Request $request, Response $response): Response
	{
		return $response->withJson(
			$this
				->segmentRepositoryFactory
				->segmentRepository($request)
				->update(
					$this->idFromRequest($request),
					PersistentSegmentInput::fromRequest($request)
				)
		);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function data(Request $request, Response $response): Response
	{
		return $response->withJson(
			RepositoryPaginatedResponse::fromRequestAndRepository(
				$request,
				$this
					->segmentRepositoryFactory
					->segmentRepository($request)
					->query(
						$this->idFromRequest($request),
						$request->getQueryParam('mode', Context::MODE_ALL),
						FieldList::default()->filterByKey(
							...$request->getQueryParam('fields', [])
						)
					)
			)
		);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function dataAutomated(Request $request, Response $response): Response
	{
		$today = new DateTime();
		return $response->withJson(
			RepositoryPaginatedResponse::fromRequestAndRepository(
				$request,
				$this
					->segmentRepositoryFactory
					->segmentRepository($request)
					->query(
						$this->idFromRequest($request),
						$request->getQueryParam('mode', Context::MODE_ALL),
						FieldList::default()->filterByKey(
							...$request->getQueryParam('fields', [])
						),
						$request->getQueryParam('startDate', $today->format('Y-m-d'))
					)
			)
		);
	}

	/**
	 * @param Request $request
	 * @return UuidInterface
	 */
	private function versionFromRequest(Request $request): UuidInterface
	{
		return Uuid::fromString($request->getParam('version', Uuid::NIL));
	}

	/**
	 * @param Request $request
	 * @param Segment $segment
	 * @param array $baseFields
	 * @return Query
	 * @throws UnknownBaseQueryException
	 * @throws InvalidUUIDException
	 * @throws OrganizationNotFoundException
	 */
	private function getQueryFromRequest(
		Request $request,
		Segment $segment,
		array $baseFields = []
	): Query {
		$organization = $this
			->organizationProvider
			->organizationForRequest($request);

		$mode = $request->getQueryParam('mode', Context::MODE_ALL);
		switch ($mode) {
			case Context::MODE_EMAIL:
				return $this
					->queryFactory
					->makeForEmail(
						$organization,
						$segment,
						$baseFields
					);
			case Context::MODE_SMS:
				return $this
					->queryFactory
					->makeForSMS(
						$organization,
						$segment,
						$baseFields
					);
			case Context::MODE_ALL:
			default:
				return $this
					->queryFactory
					->make(
						$organization,
						$segment,
						$baseFields
					);
		}
	}
}
