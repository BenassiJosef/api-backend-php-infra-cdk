<?php


namespace App\Package\Segments\Marketing;

use App\Package\Async\Notifications\JSONNotifier;
use App\Package\Segments\SegmentRepositoryFactory;
use Exception;
use Slim\Http\Request;

/**
 * Class CampaignSenderFactory
 * @package App\Package\Segments\Marketing
 */
class CampaignSenderFactory
{
    /**
     * @var SegmentRepositoryFactory $segmentRepositoryFactory
     */
    private $segmentRepositoryFactory;

    /**
     * @var JSONNotifier $notifier
     */
    private $notifier;

    /**
     * CampaignSenderFactory constructor.
     * @param SegmentRepositoryFactory $segmentRepositoryFactory
     * @param JSONNotifier $notifier
     */
    public function __construct(
        SegmentRepositoryFactory $segmentRepositoryFactory,
        JSONNotifier $notifier
    ) {
        $this->segmentRepositoryFactory = $segmentRepositoryFactory;
        $this->notifier                 = $notifier;
    }

    /**
     * @param Request $request
     * @return CampaignSender
     * @throws Exception
     */
    public function campaignSender(Request $request): CampaignSender
    {
        return new CampaignSender(
            $this->segmentRepositoryFactory->segmentRepository($request),
            $this->notifier
        );
    }
}