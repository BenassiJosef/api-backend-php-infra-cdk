<?php


namespace App\Package\Async;

/**
 * Class StubCancellationChecker
 * @package CampaignEligibility\Queues
 */
class StubCancellationChecker implements CancellationChecker
{
    /**
     * @var bool $cancelled
     */
    private $cancelled;

    /**
     * StubCancellationChecker constructor.
     * @param bool $cancelled
     */
    public function __construct(bool $cancelled = false)
    {
        $this->cancelled = $cancelled;
    }

    /**
     * @inheritDoc
     */
    public function cancelled(): bool
    {
        return $this->cancelled;
    }
}