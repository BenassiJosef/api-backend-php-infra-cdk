<?php

namespace StampedeTests\app\src\Package\Auth\ExternalServices;

use App\Package\Auth\ExternalServices\AccessCheckRequest;
use Ergebnis\Http\Method;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Slim\Http\Headers;
use Slim\Http\Request;

/**
 * Class AccessCheckerTestCase
 * @package StampedeTests\app\src\Package\Auth\ExternalServices
 */
class AccessCheckerTestCase
{
    /**
     * @param string $token
     * @param AccessCheckRequest $request
     * @return static
     */
    public static function noAccess(string $token, AccessCheckRequest $request): self
    {
        return new self(
            $token,
            $request,
            false
        );
    }

    /**
     * @param string $token
     * @param AccessCheckRequest $request
     * @return static
     */
    public static function canAccess(string $token, AccessCheckRequest $request): self
    {
        return new self(
            $token,
            $request
        );
    }

    /**
     * @var string $token
     */
    private $token;

    /**
     * @var AccessCheckRequest $request
     */
    private $request;

    /**
     * @var bool $expectedCanAccess
     */
    private $expectedCanAccess;

    /**
     * AccessCheckerTestCase constructor.
     * @param string $token
     * @param AccessCheckRequest $request
     * @param bool $expectedCanAccess
     */
    public function __construct(
        string $token,
        AccessCheckRequest $request,
        bool $expectedCanAccess = true
    ) {
        $this->token             = $token;
        $this->request           = $request;
        $this->expectedCanAccess = $expectedCanAccess;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return AccessCheckRequest
     */
    public function getRequest(): AccessCheckRequest
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    public function isExpectedCanAccess(): bool
    {
        return $this->expectedCanAccess;
    }

    /**
     * @return Request
     */
    public function request(): Request
    {
        $accessToken = $this->token;
        return new Request(
            Method::POST,
            new Uri("http://localhost:8080/auth/check"),
            new Headers(
                [
                    'Authorization' => "Bearer ${accessToken}",
                ]
            ),
            [],
            [],
            Utils::streamFor(
                json_encode($this->request)
            )
        );
    }
}