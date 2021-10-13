<?php

namespace App\Package\Async\Notifications\SNS;

use App\Package\Async\Notifications\JSONNotifier;
use App\Package\Async\Notifications\NotificationResponse;
use App\Package\Async\Notifications\SNS\Exceptions\FailedToPublishNotificationException;
use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;
use JsonSerializable;
use Throwable;

/**
 * Class SNSNotifier
 * @package App\Package\Async\Notifications\SNS
 */
class SNSNotifier implements JSONNotifier
{
    /**
     * @var SnsClient $snsClient
     */
    private $snsClient;

    /**
     * @var string $topicArn
     */
    private $topicArn;

    /**
     * SNSNotifier constructor.
     * @param SnsClient $snsClient
     * @param string $topicArn
     */
    public function __construct(
        SnsClient $snsClient,
        string $topicArn
    ) {
        $this->snsClient = $snsClient;
        $this->topicArn  = $topicArn;
    }

    /**
     * @param string $message
     * @return NotificationResponse
     * @throws FailedToPublishNotificationException
     */
    public function notify(string $message): NotificationResponse
    {
        return new NotificationResponse(
            $this->notifyRaw($message),
            $message
        );
    }

    /**
     * @param array | JsonSerializable $message
     * @return NotificationResponse
     * @throws FailedToPublishNotificationException
     */
    public function notifyJson($message): NotificationResponse
    {
        return $this->notify(json_encode($message));
    }

    /**
     * @param string $message
     * @return string
     * @throws FailedToPublishNotificationException
     */
    private function notifyRaw(string $message): string
    {
        try {
            $response = $this
                ->snsClient
                ->publish(
                    [
                        'Message'  => $message,
                        'TopicArn' => $this->topicArn,
                    ]
                );
            return $response['MessageId'];
        } catch (AwsException | Throwable $exception) {
            throw new FailedToPublishNotificationException($message, $exception);
        }
    }

}