<?php


namespace App\Controllers\Billing\Subscriptions\Cancellation;


use App\Package\RequestUser\User;

/**
 * Class CancellationEmail
 * @package App\Controllers\Billing\Subscriptions\Cancellation
 */
class CancellationEmail implements Email
{
    /**
     * @var array $recipients
     */
    private $recipients = [];

    /**
     * @var User $user
     */
    private $user;

    /**
     * @var string $reason
     */
    private $reason;

    /**
     * @var string $template
     */
    private $template = "CancelAccount";

    /**
     * CancellationEmail constructor.
     * @param array $recipients
     * @param User $user
     * @param string $reason
     */
    public function __construct(array $recipients, User $user, string $reason)
    {
        $this->recipients = $recipients;
        $this->user       = $user;
        $this->reason     = $reason;
    }

    /**
     * @param string $emailAddress
     * @return $this
     */
    public function withAdditionalRecipient(string $emailAddress): self
    {
        $email               = clone($this);
        $email->recipients[] = $email;
        return $email;
    }

    /**
     * @param array $recipients
     * @return $this
     */
    public function withRecipients(array $recipients): self
    {
        $email             = clone($this);
        $email->recipients = $recipients;
        return $email;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function withUser(User $user): self
    {
        $email       = clone($this);
        $email->user = $user;
        return $email;
    }

    /**
     * @return array
     */
    public function getSendTo(): array
    {
        $recipients = [];
        foreach ($this->recipients as $recipient) {
            $recipients[] = [
                'to'   => $recipient,
                'name' => $recipient,
            ];
        }
        return $recipients;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        $user = $this->user;
        return [
            'customer' => [
                'id'    => $user->getUid(),
                'email' => $user->getEmail(),
                'name' => $user->getFullName(),
            ],
            'serials'  => $user->getAccess(),
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
        $user    = $this->user;
        $subject = "CANCELLATION REQUEST";
        $name    = $user->getFullName();
        if ($name !== null) {
            $subject .= ": $name";
        }
        $company = $user->getCompany();
        if ($company !== null) {
            $subject .= " ($company)";
        }
        return $subject;
    }

}