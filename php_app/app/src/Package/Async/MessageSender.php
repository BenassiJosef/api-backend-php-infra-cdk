<?php


namespace App\Package\Async;


interface MessageSender
{
    public function sendMessage(string $body): Message;

    public function sendMessageJson($body): Message;
}