<?php


namespace App\Package\DataSources\Hooks;

use App\Package\Database\BaseStatement;
use App\Package\Database\RawStatementExecutor;
use App\Package\Service\ServiceRequest;
use Doctrine\ORM\EntityManager;
use Throwable;

/**
 * Class AutoReviewHook
 * @package App\Package\DataSources\Hooks
 */
class AutoServiceHook implements Hook
{

	/**
	 * @var RowFetcher $rowFetcher
	 */
	private $rowFetcher;

	/**
	 * AutoStampingHook constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(
		EntityManager $entityManager
	) {
		$this->entityManager                     = $entityManager;
		$this->rowFetcher                        = new RawStatementExecutor($entityManager);
	}

	public function notify(Payload $payload): void
	{
		$dataSource = $payload->getDataSource();

		if (!$dataSource->isVisit()) {
			return; // only send to service if is 
		}

		$service = new ServiceRequest();

		try {
			$service->post(
				$payload->getInteraction()->getOrganizationId() . '/interactions',
				[
					'id' => $payload->getInteraction()->getId(),
					'interactionId' => $payload->getInteraction()->getId(),
					'interaction' => $payload->getInteraction()->jsonSerialize(),
					'profile' => $payload->getUserProfile()->jsonSerialize(),
					'profileId' => $payload->getUserProfile()->getId()
				]
			);
		} catch (Throwable $exception) {

			if (extension_loaded('newrelic')) {
				newrelic_notice_error($exception);
			}
		}
	}

	/**
	 * @param Payload $payload
	 * @return string[]
	 * @throws DBALException
	 */
	private function serialsFromPayload(Payload $payload): array
	{
		$serialRows = $this
			->rowFetcher
			->fetchAll(
				new BaseStatement(
					"SELECT serial FROM interaction_serial WHERE interaction_id = :interactionId;",
					[
						'interactionId' => $payload->getInteraction()->getId()->toString(),
					]
				)
			);
		return from($serialRows)
			->select(
				function (array $row): string {
					return $row['serial'];
				}
			)
			->toArray();
	}
}
