<?php


namespace App\Controllers\Billing\Subscriptions\Cancellation;

use App\Package\RequestUser\User;

/**
 * Class CancellationNotificationEmail
 * @package App\Controllers\Billing\Subscriptions\Cancellation
 */
class CancellationNotificationEmail implements Email
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
     * @var string
     */
    private $template = "CancellationNotification";

    /**
     * CancellationNotificationEmail constructor.
     * @param User $user
     * @param string $reason
     */
    public function __construct(User $user, string $reason)
    {
        $this->user   = $user;
        $this->reason = $reason;
    }

    /**
     * @return array
     */
    public function getSendTo(): array
    {
        $user = $this->user;
        return [
            [
                'to'   => $user->getEmail(),
                'name' => $user->getFullName(),
            ]
        ];
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        $user = $this->user;
        return [
            'customer' => [
                'first' => $user->getFirstName(),
                'last'  => $user->getLastName(),
                'email' => $user->getEmail(),
            ],
            'reason'   => $this->reason,
            'title'    => $this->getSubject(),
        ];
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return "We're sorry to hear you're leaving";
    }
}