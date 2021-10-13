<?php


namespace App\Package\Async;

/**
 * Interface CancellationChecker
 * @package CampaignEligibility\Queues
 */
interface CancellationChecker
{
    /**
     * @return bool
     */
    public function cancelled(): bool;
}