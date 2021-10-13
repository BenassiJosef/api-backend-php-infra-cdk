<?php


namespace App\Package\Auth;


use App\Models\OauthUser;
use Exception;

class UserContext implements UserSource
{
    /**
     * @var OauthUser | null $subject
     */
    private $subject;

    /**
     * @var OauthUser | null $actor
     */
    private $actor;

    /**
     * UserContext constructor.
     * @param OauthUser|null $subject
     * @param OauthUser|null $actor
     */
    public function __construct(
        ?OauthUser $subject = null,
        ?OauthUser $actor = null
    ) {
        $this->subject = $subject;
        $this->actor   = $actor;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getUserId(): string
    {
        return $this->getUser()->getUserId();
    }

    /**
     * @return OauthUser
     * @throws Exception
     */
    public function getUser(): OauthUser
    {
        if ($this->subject !== null) {
            return $this->subject;
        }
        if ($this->actor !== null) {
            return $this->actor;
        }
        throw new Exception();
    }


    /**
     * @return OauthUser
     */
    public function subject(): ?OauthUser
    {
        return $this->subject;
    }

    /**
     * @param OauthUser $user
     * @return $this
     */
    public function withSubject(OauthUser $user): self
    {
        $ctx          = clone $this;
        $ctx->subject = $user;
        return $ctx;
    }

    /**
     * @return OauthUser|null
     */
    public function actor(): ?OauthUser
    {
        return $this->actor;
    }

    /**
     * @param OauthUser $user
     * @return $this
     */
    public function withActor(OauthUser $user): self
    {
        $ctx        = clone $this;
        $ctx->actor = $user;
        return $ctx;
    }

    /**
     * @return bool
     */
    public function subjectIsActor(): bool
    {
        if ($this->actor === null) {
            return false;
        }
        return $this->actor->getUserId() === $this->subject->getUserId();
    }
}