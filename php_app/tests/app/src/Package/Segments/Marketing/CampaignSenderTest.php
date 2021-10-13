<?php

namespace StampedeTests\app\src\Package\Segments\Marketing;

use App\Models\Organization;
use App\Models\Segments\PersistentSegment;
use App\Package\Async\Notifications\JSONNotifier;
use App\Package\Async\Notifications\NotificationResponse;
use App\Package\Segments\Database\QueryFactory;
use App\Package\Segments\Marketing\CampaignSender;
use App\Package\Segments\Marketing\Exceptions\InvalidCampaignTypeException;
use App\Package\Segments\Marketing\SendRequest;
use App\Package\Segments\Marketing\SendRequestInput;
use App\Package\Segments\PersistentSegmentInput;
use App\Package\Segments\SegmentRepository;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;

class CampaignSenderTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var Organization $organization
     */
    private $organization;
    /**
     * @var PersistentSegment
     */
    private $segment;

    protected function setUp(): void
    {
        $this->entityManager = DoctrineHelpers::createEntityManager();
        $this->entityManager->beginTransaction();

        $this->organization = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(
                [
                    'name' => 'Some Company Ltd'
                ]
            );

        $this->segment = $this
            ->segmentRepository()
            ->create(
                PersistentSegmentInput::fromArray(
                    [
                        'name'    => 'My first segment',
                        'segment' => [
                            'root' => [
                                "operator" => "and",
                                "nodes"    => [
                                    [
                                        "field"      => "serial",
                                        "comparison" => "==",
                                        "value"      => "6M38FVUOMVAZ"
                                    ],
                                    [
                                        "field"      => "first",
                                        "comparison" => "like",
                                        "mode"       => "contains",
                                        "value"      => "y"
                                    ]
                                ]
                            ]
                        ]
                    ]
                )
            );
    }

    private function segmentRepository(): SegmentRepository
    {
        return new SegmentRepository(
            $this->entityManager,
            new QueryFactory(
                $this->entityManager,
            ),
            $this->organization
        );
    }

    private function campaignSenderForNotifier(JSONNotifier $notifier): CampaignSender
    {
        return new CampaignSender(
            $this->segmentRepository(),
            $notifier
        );
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
    }


    public function testSendCampaign()
    {
        $notifier = $this
            ->getMockBuilder(JSONNotifier::class)
            ->getMock();

        $segment = $this->segment;
        $notifier
            ->method('notifyJson')
            ->willReturnCallback(
                function ($message) use ($segment): NotificationResponse {
                    /** @var SendRequest $message */
                    self::assertInstanceOf(SendRequest::class, $message);
                    self::assertEquals($segment->getId(), $message->getSegmentId());
                    return new NotificationResponse(
                        'foo',
                        json_encode($message)
                    );
                }
            );

        $campaignSender = $this->campaignSenderForNotifier($notifier);
        $response       = $campaignSender->sendCampaign(
            $this->segment->getId(),
            new SendRequestInput()
        );
        self::assertEquals('foo', $response->getNotificationResponse()->getMessageId());
        self::assertEquals($segment->getId(), $response->getSendRequest()->getSegmentId());
    }

    public function testSendCampaignFailsWithInvalidCampaignType()
    {
        self::expectException(InvalidCampaignTypeException::class);
        $notifier = $this
            ->getMockBuilder(JSONNotifier::class)
            ->getMock();

        $segment = $this->segment;
        $notifier
            ->method('notifyJson')
            ->willReturnCallback(
                function ($message) use ($segment): NotificationResponse {
                    return new NotificationResponse(
                        'foo',
                        json_encode($message)
                    );
                }
            );

        $campaignSender = $this->campaignSenderForNotifier($notifier);
        $campaignSender->sendCampaign(
            $this->segment->getId(),
            new SendRequestInput(
                'foo'
            )
        );
    }
}
