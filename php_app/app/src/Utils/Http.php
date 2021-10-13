<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 25/02/2017
 * Time: 12:35
 */

namespace App\Utils;

class Http
{
    public static function status(int $code = 500, $reason = null)
    {
        $returnArray = [
            'status' => $code
        ];
        if (!is_null($reason)) {
            $returnArray['message'] = $reason;
        } else {
            switch ($code) {
                case 200:
                    $returnArray['message'] = 'OK';
                    break;
                case 201:
                    $returnArray['message'] = 'CREATED';
                    break;
                case 202:
                    $returnArray['message'] = 'ACCEPTED';
                    break;
                case 203:
                    $returnArray['message'] = 'NON_AUTHORITATIVE_INFORMATION';
                    break;
                case 204:
                    $returnArray['message'] = 'NO_CONTENT';
                    break;
                case 205:
                    $returnArray['message'] = 'RESET_CONTENT';
                    break;
                case 206:
                    $returnArray['message'] = 'PARTIAL_CONTENT';
                    break;
                case 301:
                    $returnArray['message'] = 'MOVED_PERMANENTLY';
                    break;
                case 302:
                    $returnArray['message'] = 'FOUND';
                    break;
                case 307:
                    $returnArray['message'] = 'TEMPORARY_REDIRECT';
                    break;
                case 308:
                    $returnArray['message'] = 'PERMANENT_REDIRECT';
                    break;
                case 400:
                    $returnArray['message'] = 'BAD_REQUEST';
                    break;
                case 401:
                    $returnArray['message'] = 'UNAUTHORISED';
                    break;
                case 402:
                    $returnArray['message'] = 'PAYMENT_REQUIRED';
                    break;
                case 403:
                    $returnArray['message'] = 'FORBIDDEN';
                    break;
                case 404:
                    $returnArray['message'] = 'NOT_FOUND';
                    break;
                case 405:
                    $returnArray['message'] = 'METHOD_NOT_ALLOWED';
                    break;
                case 406:
                    $returnArray['message'] = 'NOT_ACCEPTABLE';
                    break;
                case 407:
                    $returnArray['message'] = 'PROXY_AUTHENTICATION_REQUIRED';
                    break;
                case 408:
                    $returnArray['message'] = 'REQUEST_TIME_OUT';
                    break;
                case 409:
                    $returnArray['message'] = 'CONFLICT';
                    break;
                case 410:
                    $returnArray['message'] = 'GONE';
                    break;
                case 411:
                    $returnArray['message'] = 'LENGTH_REQUIRED';
                    break;
                case 412:
                    $returnArray['message'] = 'PRECONDITION_FAILED';
                    break;
                case 413:
                    $returnArray['message'] = 'PAYLOAD_TOO_LARGE';
                    break;
                case 414:
                    $returnArray['message'] = 'URI_TOO_LONG';
                    break;
                case 415:
                    $returnArray['message'] = 'UNSUPPORTED_MEDIA_TYPE';
                    break;
                case 416:
                    $returnArray['message'] = 'RANGE_NOT_SATISFIABLE';
                    break;
                case 417:
                    $returnArray['message'] = 'EXPECTATION_FAILED';
                    break;
                case 421:
                    $returnArray['message'] = 'MISDIRECTED_REQUEST';
                    break;
                case 428:
                    $returnArray['message'] = 'PRECONDITION_REQUIRED';
                    break;
                case 429:
                    $returnArray['message'] = 'TOO_MANY_REQUESTS';
                    break;
                case 500:
                    $returnArray['message'] = 'INTERNAL_SERVER_ERROR';
                    break;
                case 501:
                    $returnArray['message'] = 'NOT_IMPLEMENTED_YET';
                    break;
                case 502:
                    $returnArray['message'] = 'BAD_GATEWAY';
                    break;
                case 503:
                    $returnArray['message'] = 'SERVICE_UNAVAILABLE';
                    break;
                case 504:
                    $returnArray['message'] = 'GATEWAY_TIMEOUT';
                    break;
            }
        }

        return $returnArray;
    }
}
