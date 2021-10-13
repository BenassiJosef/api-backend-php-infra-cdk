<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 17/03/2017
 * Time: 17:41
 */

namespace App\Utils;

use Slim\Http\Request;

class Validation
{

    static function bodyCheck(Request $request, $missing)
    {
        $body = $request->getParsedBody();
        $send = [];

        foreach ($missing as $value) {
            if (!array_key_exists($value, $body)) {
                array_push($send, $value);
            }
        }

        if (empty($send)) {
            return true;
        } else {
            return $send;
        }
    }

    static function pastRouteBodyCheck(array $body, array $required)
    {
        $send = [];

        foreach ($required as $value) {
            if (!array_key_exists($value, $body)) {
                array_push($send, $value);
            }
        }

        if (empty($send)) {
            return true;
        } else {
            return $send;
        }
    }
}
