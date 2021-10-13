<?php

namespace App\Package\Auth\Controller;

use App\Package\Auth\Exceptions\ForbiddenException;
use App\Package\Auth\ExternalServices\AccessChecker;
use App\Package\Auth\ExternalServices\AccessCheckRequest;
use App\Package\Auth\ExternalServices\Exceptions\InvalidParameterException;
use App\Package\Auth\ExternalServices\Exceptions\MissingKeyException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class ExternalServiceController
 * @package App\Package\Auth\Controller
 */
class ExternalServiceController
{
    /**
     * @var AccessChecker $accessChecker
     */
    private $accessChecker;

    /**
     * ExternalServiceController constructor.
     * @param AccessChecker $accessChecker
     */
    public function __construct(AccessChecker $accessChecker)
    {
        $this->accessChecker = $accessChecker;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws InvalidParameterException
     * @throws MissingKeyException
     * @throws ForbiddenException
     */
    public function __invoke(Request $request, Response $response): Response
    {
        return $response
            ->withJson(
                $this
                    ->accessChecker
                    ->check(
                        $request,
                        AccessCheckRequest::fromRequest($request)
                    )
            );
    }
}