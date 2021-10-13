<?php

namespace StampedeTests\app\src\Package\Organisations;

use App\Models\Organization;
use App\Models\OrganizationSettings;
use App\Package\Organisations\OrganizationSettingsService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;

class OrganizationSettingsServiceTest extends TestCase
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var Organization $organization
	 */
	private $organization;

	protected function setUp(): void
	{
		$this->entityManager = DoctrineHelpers::createEntityManager();
		$this->organization  = $this
			->entityManager
			->getRepository(Organization::class)
			->findOneBy(
				[
					'name' => 'Some Company Ltd',
				]
			);
	}

	protected function tearDown(): void
	{
		$settings = $this
			->entityManager
			->getRepository(OrganizationSettings::class)
			->findOneBy(
				[
					'organizationId' => $this->organization->getId(),
				]
			);
		$this->entityManager->remove($settings);
		$this->entityManager->flush();
	}


	public function testUpdateSettings()
	{
		$organizationSettingsService = new OrganizationSettingsService($this->entityManager);
		$settings                    = $organizationSettingsService->settings($this->organization->getId());
		$def                         = $settings->getSettings();
		self::assertFalse($def->canSendCheckoutEmail());
		$organizationSettingsService->updateSettings(
			$this->organization->getId(),
			$settings->getVersion(),
			$def->disableCheckoutEmails()
		);
		$canSendCheckoutEmail = $organizationSettingsService
			->settings($this->organization->getId())
			->getSettings()
			->canSendCheckoutEmail();
		self::assertFalse($canSendCheckoutEmail);
	}
}
