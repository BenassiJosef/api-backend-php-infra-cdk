<?php

namespace App\Package\Auth\ExternalServices;

use App\Package\Auth\Exceptions\ForbiddenException;
use App\Package\Auth\Tokens\TokenSource;
use Slim\Http\Request;

/**
 * Class AccessChecker
 * @package App\Package\Auth\ExternalServices
 */
class AccessChecker
{
    /**
     * @var TokenSource $tokenSource
     */
    private $tokenSource;

    /**
     * @var RequestAdapter $requestAdapter
     */
    private $requestAdapter;

    /**
     * AccessChecker constructor.
     * @param TokenSource $tokenSource
     * @param RequestAdapter | null $requestAdapter
     */
    public function __construct(
        TokenSource $tokenSource,
        ?RequestAdapter $requestAdapter = null
    ) {
        if ($requestAdapter === null) {
            $requestAdapter = new RequestAdapter();
        }
        $this->tokenSource    = $tokenSource;
        $this->requestAdapter = $requestAdapter;
    }

    /**
     * @param Request $request
     * @param AccessCheckRequest $accessCheckRequest
     * @return AccessCheckResponse
     * @throws ForbiddenException
     */
    public function check(Request $request, AccessCheckRequest $accessCheckRequest): AccessCheckResponse
    {
        $token      = $this
            ->tokenSource
            ->token($request);
        $canRequest = $token
            ->canRequest(
                $accessCheckRequest->getService(),
                $this
                    ->requestAdapter
                    ->adapt($accessCheckRequest)
            );
        if (!$canRequest) {
            throw new ForbiddenException();
        }
        return new AccessCheckResponse(
            $accessCheckRequest,
            $token
        );
    }
}