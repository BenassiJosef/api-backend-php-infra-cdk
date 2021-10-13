<?php

namespace App\Package\Auth\Tokens;

use App\Models\OauthAccessTokens;
use App\Package\Auth\Scopes\Scopes;
use DateTime;
use JsonSerializable;
use Slim\Http\Request;

class BaseToken implements Token, JsonSerializable
{
    /**
     * @param OauthAccessTokens $accessTokens
     * @return $this
     */
    public static function fromOauthAccessToken(OauthAccessTokens $accessTokens): self
    {
        return new self(
            $accessTokens->getAccessToken(),
            $accessTokens->getClientId(),
            Scopes::fromString($accessTokens->getScope()),
            $accessTokens->getExpires()
        );
    }

    /**
     * @var string $token
     */
    private $token;

    /**
     * @var string $clientId
     */
    private $clientId;

    /**
     * @var Scopes $scopes
     */
    private $scopes;

    /**
     * @var DateTime $expiresAt
     */
    private $expiresAt;

    /**
     * Token constructor.
     * @param string $token
     * @param string $clientId
     * @param Scopes $scopes
     * @param DateTime $expiresAt
     */
    private function __construct(
        string $token,
        string $clientId,
        Scopes $scopes,
        DateTime $expiresAt
    ) {
        $this->token     = $token;
        $this->clientId  = $clientId;
        $this->scopes    = $scopes;
        $this->expiresAt = $expiresAt;
    }

    /**
     * @param string $service
     * @param Request $request
     * @return bool
     */
    public function canRequest(string $service, Request $request): bool
    {
        return $this->scopes->canRequest($service, $request);
    }


    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return Scopes
     */
    public function getScopes(): Scopes
    {
        return $this->scopes;
    }

    /**
     * @return DateTime
     */
    public function getExpiresAt(): DateTime
    {
        return $this->expiresAt;
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
            'clientId'  => $this->clientId,
            'scopes'    => $this->scopes,
            'expiresAt' => $this->expiresAt->format(DATE_ATOM)
        ];
    }
}