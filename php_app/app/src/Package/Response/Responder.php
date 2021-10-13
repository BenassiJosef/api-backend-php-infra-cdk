<?php


namespace App\Package\Response;

use Slim\Http\Response as SlimResponse;

/**
 * Interface Responder
 * @package App\Package\Response
 */
interface Responder
{
    /**
     * @param SlimResponse $response
     * @return SlimResponse
     */
    public function respond(SlimResponse $response): SlimResponse;
}