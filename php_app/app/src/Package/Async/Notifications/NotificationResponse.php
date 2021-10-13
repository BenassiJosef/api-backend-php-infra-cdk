<?php


namespace App\Package\Async\Notifications;

use JsonSerializable;

/**
 * Class NotificationResponse
 * @package App\Package\Async\Notifications
 */
class NotificationResponse implements JsonSerializable
{
    /**
     * @var string $messageId
     */
    private $messageId;

    /**
     * @var string $message
     */
    private $message;

    /**
     * NotificationResponse constructor.
     * @param string $messageId
     * @param string $message
     */
    public function __construct(string $messageId, string $message)
    {
        $this->messageId = $messageId;
        $this->message   = $message;
    }

    /**
     * @return string
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
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
            'messageId' => $this->messageId,
        ];
    }
}