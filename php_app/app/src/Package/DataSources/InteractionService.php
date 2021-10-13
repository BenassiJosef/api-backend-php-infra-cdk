<?php


namespace App\Package\DataSources;


use App\Models\DataSources\DataSource;
use App\Models\DataSources\Interaction;
use App\Models\DataSources\InteractionProfile;
use App\Models\DataSources\InteractionSerial;
use App\Models\Organization;
use App\Models\UserProfile;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class InteractionNotFoundException
 * @package App\Package\DataSources
 */
class InteractionNotFoundException extends Exception
{
}

class InteractionAlreadyEndedException extends Exception
{
}

/**
 * Class InteractionService
 * @package App\Package\DataSources
 */
class InteractionService
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * InteractionService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $interactionId
     * @return Interaction
     * @throws InteractionNotFoundException
     */
    public function getInteractionFromString(string $interactionId): Interaction
    {
        return $this->getInteraction(
            Uuid::fromString($interactionId)
        );
    }

    /**
     * @param UuidInterface $interactionId
     * @return Interaction
     * @throws InteractionNotFoundException
     */
    public function getInteraction(UuidInterface $interactionId): Interaction
    {
        /** @var Interaction | null $interaction */
        $interaction = $this
            ->entityManager
            ->getRepository(Interaction::class)
            ->find($interactionId);
        if ($interaction === null) {
            throw new InteractionNotFoundException('Cannot find interaction');
        }
        return $interaction;
    }

    public function getRecentInteractions(
        Organization $organization,
        UserProfile $profile,
        DateTimeImmutable $since,
        bool $isVisit = true
    ): array {
        $qb   = $this
            ->entityManager
            ->createQueryBuilder();
        $expr = $qb->expr();

        $interactions = $qb->select('i')
                           ->from(Interaction::class, 'i')
                           ->join(
                               DataSource::class,
                               'ds',
                               Join::WITH,
                               'ds.id = i.dataSourceId'
                           )
                           ->leftJoin(
                               InteractionProfile::class,
                               'ip',
                               Join::WITH,
                               'i.id = ip.interactionId'
                           )
                           ->where('i.organizationId = :organizationId')
                           ->andWhere('ip.profileId = :profileId')
                           ->andWhere($expr->gt('i.createdAt', ':since'))
                           ->andWhere('ds.isVisit = :isVisit')
                           ->setParameters(
                               [
                                   'organizationId' => $organization->getId(),
                                   'profileId'      => $profile->getId(),
                                   'since'          => $since,
                                   'isVisit'        => $isVisit,
                               ]
                           )
                           ->getQuery()
                           ->getResult();
        return $interactions;
    }

    /**
     * @param string $interactionId
     * @throws InteractionNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function endInteractionFromStringId(string $interactionId): Interaction
    {
        return $this->endInteraction(
            Uuid::fromString($interactionId)
        );
    }

    /**
     * @param UuidInterface $interactionId
     * @return Interaction
     * @throws InteractionNotFoundException
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws InteractionAlreadyEndedException
     */
    public function endInteraction(UuidInterface $interactionId): Interaction
    {
        $interaction = $this->getInteraction($interactionId);
        if ($interaction->getEndedAt() !== null) {
            throw new InteractionAlreadyEndedException('Interaction has already been ended');
        }
        foreach ($interaction->getProfiles() as $interactionProfile) {
            $interactionProfile->getProfile()->validate();
        }
        $interaction->end();
        $this->entityManager->flush();
        if (extension_loaded('newrelic')) {
            newrelic_record_custom_event(
                'InteractionEnded',
                [
                    'interactionId'       => $interactionId->toString(),
                    'interactionDuration' => $interaction->lengthSeconds(),
                ]
            );
            $length = $interaction->lengthSeconds() * 1000;
            newrelic_custom_metric('Custom/InteractionLength', (float)$length);
        }
        return $interaction;
    }
}