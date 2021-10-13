<?php

namespace App\Package\Auth\Tokens;

use App\Models\OauthAccessTokens;
use App\Models\OauthUser;
use App\Package\Auth\Access\User\UserRequestValidatorSource;
use App\Package\Auth\AggregateRequestValidator;
use App\Package\Auth\Tokens\Exceptions\InvalidUserIDException;
use App\Package\Auth\UserSource;
use DateTime;
use Doctrine\ORM\EntityManager;
use JsonSerializable;
use Slim\Http\Request;
use App\Package\Auth\Scopes\Scopes;

/**
 * Class UserToken
 * @package App\Package\Auth\Tokens
 */
class UserToken implements Token, UserSource, JsonSerializable
{
    /**
     * @param EntityManager $entityManager
     * @param UserRequestValidatorSource $validatorFactory
     * @param OauthAccessTokens $token
     * @return static
     */
    public static function fromOauthAccessToken(
        EntityManager $entityManager,
        UserRequestValidatorSource $validatorFactory,
        OauthAccessTokens $token
    ): self {
        return new self(
            $entityManager,
            $validatorFactory,
            $token->getUserId(),
            BaseToken::fromOauthAccessToken($token)
        );
    }

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var UserRequestValidatorSource $validatorFactory
     */
    private $validatorFactory;

    /**
     * @var string $userId
     */
    private $userId;

    /**
     * @var OauthUser | null $user
     */
    private $user;

    /**
     * @var BaseToken $baseToken
     */
    private $baseToken;

    /**
     * UserToken constructor.
     * @param EntityManager $entityManager
     * @param UserRequestValidatorSource $validatorFactory
     * @param string $userId
     * @param BaseToken $baseToken
     */
    public function __construct(
        EntityManager $entityManager,
        UserRequestValidatorSource $validatorFactory,
        string $userId,
        BaseToken $baseToken
    ) {
        $this->entityManager    = $entityManager;
        $this->validatorFactory = $validatorFactory;
        $this->userId           = $userId;
        $this->baseToken        = $baseToken;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }


    /**
     * @inheritDoc
     * @throws InvalidUserIDException
     */
    public function canRequest(string $service, Request $request): bool
    {
        return AggregateRequestValidator::fromRequestValidators(
            $this->getScopes(),
            $this
                ->validatorFactory
                ->requestValidator(
                    $this->getUser()
                )
        )->canRequest($service, $request);
    }

    /**
     * @return OauthUser
     * @throws InvalidUserIDException
     */
    public function getUser(): OauthUser
    {
        if ($this->user === null) {
            $this->user = $this->fetchUser();
        }
        return $this->user;
    }

    /**
     * @return OauthUser
     * @throws InvalidUserIDException
     */
    private function fetchUser(): OauthUser
    {
        /** @var OauthUser | null $user */
        $user = $this
            ->entityManager
            ->getRepository(OauthUser::class)
            ->findOneBy(
                [
                    'uid' => $this->userId,
                ]
            );
        if ($user === null) {
            throw new InvalidUserIDException($this->userId);
        }
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function getToken(): string
    {
        return $this->baseToken->getToken();
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return $this->baseToken->getClientId();
    }

    /**
     * @inheritDoc
     */
    public function getScopes(): Scopes
    {
        return $this->baseToken->getScopes();
    }

    /**
     * @inheritDoc
     */
    public function getExpiresAt(): DateTime
    {
        return $this->baseToken->getExpiresAt();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return [
            'clientId' => $this->getClientId(),
            'userId'   => $this->userId,
            'scopes'    => $this->getScopes(),
            'expiresAt'  => $this->getExpiresAt()->format(DATE_ATOM),
        ];
    }
}