<?php


namespace App\Package\Async;
/**
 * Interface QueueHandler
 * @package CampaignEligibility
 */
interface QueueHandler
{
    /**
     * @param Message $message
     * @return mixed
     */
    public function handleMessage(Message $message);
}