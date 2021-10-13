<?php


namespace App\Controllers\Billing\Subscriptions\Cancellation;


use App\Package\RequestUser\User;

/**
 * Class CancellationRequest
 * @package App\Controllers\Billing\Subscriptions\Cancellation
 */
class CancellationRequest
{
    /**
     * @var User $user
     */
    private $user;

    /**
     * @var string $reason
     */
    private $reason;

    /**
     * CancellationRequest constructor.
     * @param User $user
     * @param string $reason
     */
    public function __construct(User $user, string $reason)
    {
        $this->user   = $user;
        $this->reason = $reason;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}