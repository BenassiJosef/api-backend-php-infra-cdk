<?php


namespace App\Package\Segments\Controller;

use App\Package\Exceptions\InvalidUUIDException;
use App\Package\Segments\Exceptions\PersistentSegmentNotFoundException;
use App\Package\Segments\Marketing\CampaignSender;
use App\Package\Segments\Marketing\CampaignSenderFactory;
use App\Package\Segments\Marketing\Exceptions\InvalidCampaignTypeException;
use App\Package\Segments\Marketing\SendRequestInput;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class MarketingController
 * @package App\Package\Segments\Controller
 */
class SegmentMarketingController
{
    /**
     * @var CampaignSenderFactory $campaignSenderFactory
     */
    private $campaignSenderFactory;

    /**
     * MarketingController constructor.
     * @param CampaignSenderFactory $campaignSenderFactory
     */
    public function __construct(CampaignSenderFactory $campaignSenderFactory)
    {
        $this->campaignSenderFactory = $campaignSenderFactory;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws InvalidUUIDException
     * @throws PersistentSegmentNotFoundException
     * @throws InvalidCampaignTypeException
     */
    public function sendCampaign(Request $request, Response $response): Response
    {
        $resp = $this
            ->campaignSender($request)
            ->sendCampaign(
                $this->segmentIdFromRequest($request),
                SendRequestInput::fromRequest($request)
            );
        return $response->withJson($resp);
    }

    /**
     * @param Request $request
     * @return UuidInterface
     * @throws InvalidUUIDException
     */
    private function segmentIdFromRequest(Request $request): UuidInterface
    {
        $segmentId = $request->getAttribute('id', '');
        try {
            return Uuid::fromString($segmentId);
        } catch (Exception $exception) {
            throw new InvalidUUIDException(
                $segmentId,
                'segmentId',
                $exception
            );
        }
    }

    private function campaignSender(Request $request): CampaignSender
    {
        return $this
            ->campaignSenderFactory
            ->campaignSender($request);
    }
}