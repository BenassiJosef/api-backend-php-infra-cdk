<?php

namespace App\Package\Loyalty\StampScheme;

use App\Models\Loyalty\LoyaltySecondary;
use App\Models\Loyalty\LoyaltyStampScheme;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;

class SchemeSecondaryId
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;


    /**
     * @var LoyaltyStampScheme
     */
    private $stampScheme;

    /**
     * OrganizationStampScheme constructor.
     * @param EntityManager $entityManager
     * @param LoyaltyStampScheme $stampScheme
     */
    public function __construct(
        EntityManager $entityManager,
        LoyaltyStampScheme $stampScheme
    ) {
        $this->entityManager = $entityManager;
        $this->stampScheme   = $stampScheme;
    }

    public function create(array $data): LoyaltySecondary
    { 
        $secondaryId = LoyaltySecondary::fromArray($this->stampScheme, $data);
        $this->entityManager->persist($secondaryId);
        $this->entityManager->flush();
        return $secondaryId;
    }

    public function update(string $id, bool $active, ?string $serial): ?LoyaltySecondary
    {

        /**
         * @var LoyaltySecondary $secondaryId
         */
        $secondaryId = $this->entityManager->getRepository(LoyaltySecondary::class)->find(Uuid::fromString($id));

        if (!is_null($secondaryId)) {
            $secondaryId->setActive($active);
            $secondaryId->setSerial($serial);
            $this->entityManager->persist($secondaryId);
            $this->entityManager->flush();
        }

        return $secondaryId;
    }

    /**
     * @return LoyaltySecondary[]
     */

    public function getSecondaryIds(): array
    {

        $qb       = $this->entityManager->createQueryBuilder();
        $expr     = $qb->expr();
        $schemeId = $this->stampScheme->getId()->toString();
        /**
         * @var LoyaltySecondary[] $results
         * */
        $results = $qb
            ->select('o')
            ->from(LoyaltySecondary::class, 'o')
            ->where($expr->eq("o.schemeId", ':schemeId'))
            ->andWhere($expr->isNull('o.deletedAt'))
            ->setParameter('schemeId', $schemeId)
            ->getQuery()
            ->getResult();

        $res = [];
        foreach ($results as $item) {
            $res[] = $item->jsonSerialize();
        }

        return $res;
    }
}
