<?php

namespace App\Package\Clients\InternalOAuth;

use Psr\Http\Message\RequestInterface;

/**
 * Class Middleware
 * @package App\Package\Clients\InternalOAuth
 */
class Middleware
{
    /**
     * @var TokenSource $tokenSource
     */
    private $tokenSource;

    /**
     * Middleware constructor.
     * @param TokenSource $tokenSource
     */
    public function __construct(TokenSource $tokenSource)
    {
        $this->tokenSource = $tokenSource;
    }

    /**
     * @return callable
     */
    public function middleware(): callable
    {
        $tokenSource = $this->tokenSource;
        return function (callable $handler) use ($tokenSource) {
            return function (RequestInterface $request, array $options) use ($handler, $tokenSource) {
                return $handler(
                    $request->withHeader(
                        'Authorization',
                        $tokenSource->token()->header()
                    ),
                    $options
                );
            };
        };
    }
}