<?php


namespace App\Package\Response;

use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Slim\Http\Response as SlimResponse;

class ResponseFactory
{
    public static function response(SlimResponse $slimResp, Response $response): SlimResponse
    {
        return $slimResp
            ->withHeader('Content-Type', 'application/problem+json')
            ->withJson($response, $response->getStatus());
    }

    public static function internalServerError(SlimResponse $response): SlimResponse
    {
        return self::response(
            $response,
            ProblemResponse::fromStatus(
                StatusCodes::HTTP_INTERNAL_SERVER_ERROR,
                'INTERNAL_SERVER_ERROR'
            )
        );
    }
}
