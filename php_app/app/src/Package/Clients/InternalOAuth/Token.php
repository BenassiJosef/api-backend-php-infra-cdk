<?php

namespace App\Package\Clients\InternalOAuth;

/**
 * Class Token
 * @package App\Package\Clients\InternalOAuth
 */
class Token implements \JsonSerializable
{
    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['access_token'],
            $data['expires_in'],
            $data['token_type'],
            $data['scope']
        );
    }

    /**
     * @var string $accessToken
     */
    private $accessToken;

    /**
     * @var int $expiresIn
     */
    private $expiresIn;

    /**
     * @var string $tokenType
     */
    private $tokenType;

    /**
     * @var string $scope
     */
    private $scope;

    /**
     * Token constructor.
     * @param string $accessToken
     * @param int $expiresIn
     * @param string $tokenType
     * @param string $scope
     */
    public function __construct(
        string $accessToken,
        int $expiresIn,
        string $tokenType,
        string $scope
    ) {
        $this->accessToken = $accessToken;
        $this->expiresIn   = $expiresIn;
        $this->tokenType   = $tokenType;
        $this->scope       = $scope;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function header(): string
    {
        return sprintf('%s %s', $this->tokenType, $this->accessToken);
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
            "access_token" => $this->accessToken,
            "expires_in"   => $this->expiresIn,
            "token_type"   => $this->tokenType,
            "scope"        => $this->scope,
        ];
    }


}