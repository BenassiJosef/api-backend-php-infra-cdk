<?php


namespace App\Package\Async;

use JsonSerializable;

interface BulkMessageSender
{
    /**
     * @param string[] $bodies
     * @return Message[]
     */
    public function sendMessages(array $bodies): array;

    /**
     * @param array[] | JsonSerializable[] $messages
     * @return Message[] | array
     */
    public function sendMessagesJson(array $messages): array;
}