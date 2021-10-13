<?php

namespace App\Package\Async\Notifications\SNS\Exceptions;

use Exception;
use Slim\Http\StatusCode;
use Throwable;

/**
 * Class FailedToSendNotificationException
 * @package App\Package\Async\Notifications\SNS\Exceptions
 */
class FailedToPublishNotificationException extends SNSException
{
    /**
     * FailedToSendNotificationException constructor.
     * @param string $message
     * @param Throwable|null $previous
     * @throws Exception
     */
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct(
            $this->message($message, $previous),
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            [
                'message' => $message,
            ],
            $previous
        );
    }

    private function message(string $message, Throwable $previous = null)
    {
        $message = "Failed to send message (${message}) to SQS";
        if ($previous === null) {
            return $message;
        }
        $previousMessage = $previous->getMessage();
        return $message . " because of (${previousMessage})";
    }
}