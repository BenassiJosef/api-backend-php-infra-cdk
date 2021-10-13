<?php
declare(strict_types=1);

namespace App\Package\Organisations;

use App\Models\Organization;
use Doctrine\ORM\EntityManager;
use Exception;
use Ramsey\Uuid\UuidInterface;

class IdsType
{
    /** @var UuidInterface */
    private $orgId;
    /** @var UuidInterface */
    private $adminId;

    /**
     * IdsType constructor.
     * @param UuidInterface $orgId
     * @param UuidInterface $adminId
     */
    public function __construct(UuidInterface $orgId, UuidInterface $adminId)
    {
        $this->orgId   = $orgId;
        $this->adminId = $adminId;
    }

    /**
     * @return UuidInterface
     */
    public function getOrgId(): UuidInterface
    {
        return $this->orgId;
    }

    /**
     * @return string
     */
    public function getAdminId(): UuidInterface
    {
        return $this->adminId;
    }
}

class OrganisationIdProviderException extends Exception {}


class OrganisationIdProvider {
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * OrganisationIdProvider constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $orgOrAdminId
     * @return IdsType
     * @throws OrganisationIdProviderException
     */
    public function getIds(string $orgOrAdminId): IdsType
    {
        $repository = $this->entityManager->getRepository(Organization::class);
        /** @var Organization $org */
        $org = $repository->find($orgOrAdminId);
        if (!is_null($org)) {
            return new IdsType($org->getId(), $org->getOwnerId());
        }
        $org = $repository->findOneBy(["ownerId" => $orgOrAdminId]);
        if (is_null($org)) {
            throw new OrganisationIdProviderException("No organisation found for ${orgOrAdminId}");
        }
        return new IdsType($org->getId(), $org->getOwnerId());
    }
}