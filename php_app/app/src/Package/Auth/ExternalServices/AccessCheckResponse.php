<?php

namespace App\Package\Auth\ExternalServices;

use App\Package\Auth\Tokens\Token;

/**
 * Class AccessCheckResponse
 * @package App\Package\Auth\ExternalServices
 */
class AccessCheckResponse implements \JsonSerializable
{
    /**
     * @var AccessCheckRequest $accessCheckRequest
     */
    private $accessCheckRequest;

    /**
     * @var Token $token
     */
    private $token;

    /**
     * AccessCheckResponse constructor.
     * @param AccessCheckRequest $accessCheckRequest
     * @param Token $token
     */
    public function __construct(
        AccessCheckRequest $accessCheckRequest,
        Token $token
    ) {
        $this->accessCheckRequest = $accessCheckRequest;
        $this->token              = $token;
    }

    /**
     * @return AccessCheckRequest
     */
    public function getAccessCheckRequest(): AccessCheckRequest
    {
        return $this->accessCheckRequest;
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
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
            'request' => $this->accessCheckRequest,
            'token'   => $this->token,
        ];
    }
}