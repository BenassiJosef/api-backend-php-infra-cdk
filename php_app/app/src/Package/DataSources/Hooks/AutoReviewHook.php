<?php


namespace App\Package\DataSources\Hooks;


use App\Models\DataSources\Interaction;
use App\Models\DataSources\InteractionSerial;
use App\Models\Locations\LocationSettings;
use App\Models\Loyalty\Exceptions\AlreadyActivatedException;
use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\NegativeStampException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\Exceptions\StampedTooRecentlyException;
use App\Models\Reviews\ReviewSettings;
use App\Package\Database\BaseStatement;
use App\Package\Database\RawStatementExecutor;
use App\Package\Database\RowFetcher;
use App\Package\Loyalty\OrganizationLoyaltyServiceFactory;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Reviews\DelayedReviewSender;
use App\Package\Reviews\ReviewService;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Throwable;

/**
 * Class AutoReviewHook
 * @package App\Package\DataSources\Hooks
 */
class AutoReviewHook implements Hook
{

    /**
     * @var DelayedReviewSender $delayedReviewSender
     */
    private $delayedReviewSender;

    /**
     * AutoReviewHook constructor.
     * @param DelayedReviewSender $delayedReviewSender
     */
    public function __construct(DelayedReviewSender $delayedReviewSender)
    {
        $this->delayedReviewSender = $delayedReviewSender;
    }


    public function notify(Payload $payload): void
    {
        $dataSource = $payload->getDataSource();

        if (!$dataSource->isVisit()) {
            return; // only auto-review if they're visiting the venue
        }
        try {
            $this->delayedReviewSender->send(
                $payload->getUserProfile(),
                $payload->getInteraction()->getOrganizationId(),
                $payload->getInteraction()->getId(),
            );
        } catch (Throwable $exception) {
            if (extension_loaded('newrelic')) {
                newrelic_notice_error($exception);
            }
        }
    }
}
