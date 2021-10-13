<?php


namespace App\Package\Billing;

use App\Models\Billing\Organisation\SMSLedger;
use App\Models\Organization;
use App\Package\Billing\Exceptions\SMSTransactionInsufficientBalance;
use App\Package\Billing\Exceptions\SMSTransactionNotFoundException;
use Doctrine\ORM\EntityManager;
use App\Package\Organisations\OrganizationService;
use App\Package\Pagination\PaginatedResponse;
use App\Package\Reports\FromQuery;
use Doctrine\ORM\Query\Expr\OrderBy;
use Slim\Http\Request;
use Slim\Http\Response;

class SMSTransactions
{

	/**
	 * @var OrganizationService
	 */
	private $organizationService;

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	public function __construct(OrganizationService $organizationService, EntityManager $entityManager)
	{
		$this->organizationService  = $organizationService;
		$this->entityManager = $entityManager;
	}

	public function canDeductCredits(Organization $organization, int $credits): bool
	{
		$ledgerItem = $this->getMostRecentLedgerItem($organization);
		if (is_null($ledgerItem)) {
			return false;
		}
		return $ledgerItem->canUseCredits($credits);
	}

	public function deductCredits(Organization $organization,  int $credits, string $reason = ''): SMSLedger
	{
		$ledgerItem = $this->getMostRecentLedgerItem($organization);

		if (is_null($ledgerItem)) {
			throw new SMSTransactionNotFoundException();
		}

		if (!$ledgerItem->canUseCredits($credits)) {
			throw new SMSTransactionInsufficientBalance();
		}

		$newTransation = new SMSLedger($organization, $reason, $ledgerItem);
		$newTransation->deductCredit($credits);
		$this->entityManager->persist($newTransation);
		$this->entityManager->flush();
		return $newTransation;
	}

	public function addCredits(Organization $organization, int $credits): SMSLedger
	{
		$ledgerItem = $this->getMostRecentLedgerItem($organization);
		$newTransation = new SMSLedger($organization, 'purchase', $ledgerItem);
		$newTransation->addCredit($credits);
		$this->entityManager->persist($newTransation);
		$this->entityManager->flush();
		return $newTransation;
	}

	public function getMostRecentLedgerItem(Organization $organization): ?SMSLedger
	{
		$qb = $this->entityManager->createQueryBuilder();
		$expr  = $qb->expr();
		return  $qb
			->select('s')
			->from(SMSLedger::class, 's')
			->where($expr->eq('s.organizationId', ':organizationId'))
			->setParameter('organizationId', $organization->getId())
			->orderBy('s.createdAt', 'DESC')
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();
	}

	/**
	 * @param FromQuery $params
	 * @return PaginatedResponse
	 */

	public function getSmsLedgerTransactions(FromQuery $params): PaginatedResponse
	{

		$qb = $this->entityManager
			->createQueryBuilder();
		$expr = $qb->expr();

		$query = $qb->select('s')
			->from(SMSLedger::class, 's')
			->where($expr->eq('s.organizationId', ':organizationId'))
			->setParameter('organizationId', $params->getOrganizationId())
			->orderBy(new OrderBy('s.createdAt', $params->getSort()))
			->setMaxResults($params->getLimit())
			->setFirstResult($params->getOffset())
			->getQuery();

		return new PaginatedResponse($query);
	}



	public function testRoute(Request $request, Response $response): Response
	{

		$organization = $this->organizationService->getOrganisationById($request->getParsedBodyParam('orgId', null));
		$credit = $request->getParsedBodyParam('credit', null);
		$debt = $request->getParsedBodyParam('debt', null);
		$res = [];
		if (!is_null($credit)) {
			$res[] = $this->addCredits($organization, $credit);
		}
		if (!is_null($debt)) {
			$res[] = $this->deductCredits($organization, $debt);
		}
		return  $response->withJson($res);
	}
}
