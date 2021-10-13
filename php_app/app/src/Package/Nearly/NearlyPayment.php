<?php

namespace App\Package\Nearly;

use App\Controllers\Integrations\Stripe\_StripeCardsController;
use App\Controllers\Integrations\Stripe\_StripeCustomerController;
use App\Models\LocationPlan;
use App\Models\LocationPlanSerial;
use App\Models\User\UserDevice;
use App\Models\UserData;
use DateTime;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class NearlyPayment
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;


	/**
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * MarketingController constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(
		EntityManager $entityManager,
		Logger $logger
	) {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
	}

	public function load(NearlyOutput $output): NearlyOutput
	{
		if ($output->getLocation()->getType() !== 1) {
			return $output;
		}

		$serial = $output->getLocation()->getSerial();
		$plans          = $this->getPlans($serial);
		$output->setPaymentPlans($plans);

		if (is_null($output->getProfile())) {
			return $output;
		}

		$profileId = $output->getProfile()->getId();
		$devices = $this->getDevices($profileId, $serial);

		if (count($devices) === 0) {
			return $output;
		}

		$transactions                 = $this->getTransactions(
			$profileId,
			$serial
		);

		if (is_array($transactions)) {
			$output->setPaymentDevices($devices);
			$output->setPaymentTransactions($transactions);
		}

		if (!$output->getLocation()->getUsingStripe()) {
			return $output;
		}

		$stripeCustomerController = new _StripeCustomerController($this->logger, $this->entityManager);
		$stripeCustomer           = $stripeCustomerController->createCustomer(
			$profileId,
			$serial,
			null
		);

		if ($stripeCustomer['status'] === 200) {
			$output->setStripeCustomerId($stripeCustomer['message']['stripeCustomerId']);
			$newCardsController = new _StripeCardsController($this->logger, $this->entityManager);
			$cardsRequest       = $newCardsController->stripeListCards(
				$stripeCustomer['message']['stripe_user_id'],
				$stripeCustomer['message']['stripeCustomerId']
			);

			$output->setStripeCustomerId($stripeCustomer['message']['stripeCustomerId']);
			if ($cardsRequest['status'] === 200) {
				if (is_array($cardsRequest['message']->data)) {
					$output->setStripePaymentMethods($cardsRequest['message']->data);
				}
			}
		}

		return $output;
	}

	public function getTransactions(int $profileId, string $serial)
	{
		$query = 'SELECT up.id, 
                  SUM(up.duration) as duration,
                  SUM(up.payment_amount) as cost, 
                  SUM(up.devices) as devices,
                  COUNT(up.id) as  payments,
                  up.profileId
             FROM 
            user_profile u
            JOIN user_payments up ON u.id = up.profileId
            WHERE u.id = :id
            AND up.creationdate + INTERVAL duration HOUR > NOW()
            AND serial = :serial';

		$builder = $this->entityManager->getConnection();
		$stmt    = $builder->prepare($query);

		$stmt->execute(
			[
				'id'     => $profileId,
				'serial' => $serial
			]
		);
		return $stmt->fetch();
	}


	/**
	 * @return LocationPlan[]
	 */
	public function getPlans($serial): array
	{
		return $this->entityManager->createQueryBuilder()
			->select('lp')
			->from(LocationPlan::class, 'lp')
			->join(LocationPlanSerial::class, 'lps', 'WITH', 'lp.id = lps.planId')
			->where('lps.serial = :serial')
			->andWhere('lp.isDeleted = 0')
			->setParameter('serial', $serial)
			->orderBy('lp.cost', 'ASC')
			->getQuery()
			->getResult();
	}

	/**
	 * @return UserDevice[]
	 */
	public function getDevices(int $profileId, string $serial): array
	{
		return $this->entityManager->createQueryBuilder()
			->select('u')
			->from(UserDevice::class, 'u')
			->join(UserData::class, 'ud', 'WITH', 'u.mac = ud.mac')
			->where('ud.serial = :serial')
			->andWhere('ud.profileId = :id')
			->andWhere('ud.auth = :a')
			->andWhere('ud.type = :t')
			->setParameter('serial', $serial)
			->setParameter('id', $profileId)
			->setParameter('a', 1)
			->setParameter('t', 'paid')
			->groupBy('u.mac')
			->getQuery()
			->getResult();
	}

	public function checkHybridDataUsage(int $profileId, string $serial, $sizeLimit = 0): bool
	{
		$date  = new DateTime();
		$check = $this->entityManager->createQueryBuilder()
			->select('SUM(u.dataDown) dd')
			->from(UserData::class, 'u')
			->where('u.serial = :serial')
			->andWhere('u.profileId = :id')
			->andWhere('u.timestamp > :past')
			->setParameter('serial', $serial)
			->setParameter('id', $profileId)
			->setParameter('past', $date->modify('-1 month'))
			->getQuery()
			->getArrayResult();



		if (!empty($check)) {
			if (!is_null($check[0]['dd'])) {
				$toMB = ($check[0]['dd'] / 1024) / 1024;
				if ($toMB >= $sizeLimit) {
					return true;
				}
			}
		}

		return false;
	}
}
