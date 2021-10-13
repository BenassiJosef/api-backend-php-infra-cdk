<?php

namespace App\Package\Segments\Marketing;

use App\Package\Async\Notifications\NotificationResponse;

/**
 * Class CampaignSendResponse
 * @package App\Package\Segments\Marketing
 */
class CampaignSendResponse implements \JsonSerializable
{
    /**
     * @var NotificationResponse $notificationResponse
     */
    private $notificationResponse;

    /**
     * @var SendRequest $sendRequest
     */
    private $sendRequest;

    /**
     * CampaignSendResponse constructor.
     * @param NotificationResponse $notificationResponse
     * @param SendRequest $sendRequest
     */
    public function __construct(
        NotificationResponse $notificationResponse,
        SendRequest $sendRequest
    ) {
        $this->notificationResponse = $notificationResponse;
        $this->sendRequest          = $sendRequest;
    }

    /**
     * @return NotificationResponse
     */
    public function getNotificationResponse(): NotificationResponse
    {
        return $this->notificationResponse;
    }

    /**
     * @return SendRequest
     */
    public function getSendRequest(): SendRequest
    {
        return $this->sendRequest;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return [
            'id'      => $this->notificationResponse->getMessageId(),
            'request' => $this->sendRequest,
        ];
    }
}