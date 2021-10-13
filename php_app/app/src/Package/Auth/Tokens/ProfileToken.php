<?php

namespace App\Package\Auth\Tokens;

use App\Models\OauthAccessTokens;
use App\Models\UserProfile;
use App\Package\Auth\Access\Profile\ProfileRequestValidatorFactory;
use App\Package\Auth\Access\Profile\ProfileRequestValidatorSource;
use App\Package\Auth\AggregateRequestValidator;
use App\Package\Auth\ProfileSource;
use App\Package\Auth\Scopes\Scopes;
use App\Package\Auth\Tokens\Exceptions\InvalidProfileIdException;
use DateTime;
use Doctrine\ORM\EntityManager;
use JsonSerializable;
use Slim\Http\Request;

/**
 * Class ProfileToken
 * @package App\Package\Auth\Tokens
 */
class ProfileToken implements Token, ProfileSource, JsonSerializable
{
    /**
     * @param EntityManager $entityManager
     * @param OauthAccessTokens $token
     * @return static
     */
    public static function fromOauthAccessToken(
        EntityManager $entityManager,
        OauthAccessTokens $token
    ): self {
        return new self(
            $entityManager,
            (int)$token->getUserId(),
            BaseToken::fromOauthAccessToken($token)
        );
    }

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var int $profileId
     */
    private $profileId;

    /**
     * @var BaseToken $baseToken
     */
    private $baseToken;

    /**
     * @var UserProfile | null $profile
     */
    private $profile;

    /**
     * @var ProfileRequestValidatorSource $profileRequestValidatorFactory
     */
    private $profileRequestValidatorFactory;

    /**
     * ProfileToken constructor.
     * @param EntityManager $entityManager
     * @param int $profileId
     * @param BaseToken $baseToken
     * @param ProfileRequestValidatorSource | null $profileRequestValidatorFactory
     */
    public function __construct(
        EntityManager $entityManager,
        int $profileId,
        BaseToken $baseToken,
        ?ProfileRequestValidatorSource $profileRequestValidatorFactory = null
    ) {
        if ($profileRequestValidatorFactory === null) {
            $profileRequestValidatorFactory = new ProfileRequestValidatorFactory();
        }
        $this->entityManager                  = $entityManager;
        $this->profileId                      = $profileId;
        $this->baseToken                      = $baseToken;
        $this->profileRequestValidatorFactory = $profileRequestValidatorFactory;
    }


    /**
     * @inheritDoc
     * @throws InvalidProfileIdException
     */
    public function canRequest(string $service, Request $request): bool
    {
        return AggregateRequestValidator::fromRequestValidators(
            $this->getScopes(),
            $this
                ->profileRequestValidatorFactory
                ->requestValidator(
                    $this->getProfile()
                )
        )->canRequest(
            $service,
            $request
        );
    }

    /**
     * @return UserProfile
     * @throws InvalidProfileIdException
     */
    public function getProfile(): UserProfile
    {
        if ($this->profile === null) {
            $this->profile = $this->fetchProfile();
        }
        return $this->profile;
    }

    /**
     * @return UserProfile
     * @throws InvalidProfileIdException
     */
    private function fetchProfile(): UserProfile
    {
        /** @var UserProfile | null $profile */
        $profile = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->find($this->profileId);
        if ($profile === null) {
            throw new InvalidProfileIdException($this->profileId);
        }
        return $profile;
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
            'profileId' => $this->profileId,
            'clientId'  => $this->getClientId(),
            'scopes'    => $this->getScopes(),
            'expiresAt' => $this->getExpiresAt()->format(DATE_ATOM)
        ];
    }
}