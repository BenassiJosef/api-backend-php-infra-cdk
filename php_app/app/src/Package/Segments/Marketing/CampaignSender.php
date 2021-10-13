<?php


namespace App\Package\Segments\Marketing;


use App\Package\Async\Notifications\JSONNotifier;
use App\Package\Segments\Exceptions\PersistentSegmentNotFoundException;
use App\Package\Segments\SegmentRepository;
use Ramsey\Uuid\UuidInterface;

/**
 * Class CampaignSender
 * @package App\Package\Segments\Marketing
 */
class CampaignSender
{
    /**
     * @var SegmentRepository $segmentRepository
     */
    private $segmentRepository;

    /**
     * @var JSONNotifier $notifier
     */
    private $notifier;

    /**
     * CampaignSender constructor.
     * @param SegmentRepository $segmentRepository
     * @param JSONNotifier $notifier
     */
    public function __construct(
        SegmentRepository $segmentRepository,
        JSONNotifier $notifier
    ) {
        $this->segmentRepository = $segmentRepository;
        $this->notifier          = $notifier;
    }

    /**
     * @param UuidInterface $segmentId
     * @param SendRequestInput $input
     * @return mixed
     * @throws Exceptions\InvalidCampaignTypeException
     * @throws PersistentSegmentNotFoundException
     */
    public function sendCampaign(
        UuidInterface $segmentId,
        SendRequestInput $input
    ): CampaignSendResponse {
        $request = new SendRequest(
            $input->getCampaignType(),
            $this
                ->segmentRepository
                ->fetchSingle($segmentId),
            $input->getTemplate()
        );
        return new CampaignSendResponse(
            $this
                ->notifier
                ->notifyJson($request),
            $request
        );
    }
}