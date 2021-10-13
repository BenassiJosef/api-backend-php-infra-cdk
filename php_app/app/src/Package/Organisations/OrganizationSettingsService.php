<?php


namespace App\Package\Organisations;


use App\Models\Organization;
use App\Models\OrganizationSettings;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Throwable;

class OrganizationSettingsService
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * OrganizationSettingsService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $organizationId
     * @return OrganizationSettings
     * @throws OrganisationNotFoundException
     */
    public function settingsForOrgIdString(string $organizationId): OrganizationSettings
    {
        return $this->settings(
            Uuid::fromString($organizationId),
        );
    }

    /**
     * @param UuidInterface $organizationId
     * @return OrganizationSettings
     * @throws OrganisationNotFoundException
     * @throws Exception
     */
    public function settings(UuidInterface $organizationId): OrganizationSettings
    {
        /** @var OrganizationSettings | null $orgSettings */
        $orgSettings = $this
            ->entityManager
            ->getRepository(OrganizationSettings::class)
            ->findOneBy(
                [
                    'organizationId' => $organizationId
                ]
            );
        if ($orgSettings !== null) {
            return $orgSettings;
        }
        /** @var Organization | null $organization */
        $organization = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->find($organizationId);

        if ($organization === null) {
            throw new OrganisationNotFoundException("organization does not exist");
        }
        return new OrganizationSettings(
            $organization
        );
    }

    /**
     * @param UuidInterface $organizationId
     * @param UuidInterface $version
     * @param OrganizationSettingsDefinition $definition
     * @return OrganizationSettings
     * @throws OrganisationNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function updateSettings(
        UuidInterface $organizationId,
        UuidInterface $version,
        OrganizationSettingsDefinition $definition
    ): OrganizationSettings {
        $this->entityManager->beginTransaction();
        try {
            $settings = $this->settings($organizationId);
            $settings->setSettings($definition, $version);
            $this->entityManager->persist($settings);
            $this->entityManager->flush();
        } catch (Throwable $throwable) {
            $this->entityManager->rollback();
            throw $throwable;
        }
        $this->entityManager->commit();
        return $settings;
    }
}