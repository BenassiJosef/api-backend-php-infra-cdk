<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 15/02/2017
 * Time: 14:45
 */

namespace App\Controllers\Billing\Subscriptions;

use App\Controllers\Integrations\ChargeBee\_ChargeBeeHandleErrors;
use Slim\Http\Response;
use Slim\Http\Request;

/** WILL NEED REFACTOR =/ */
class _HostedPages
{
    protected $errorHandler;

    public function __construct()
    {
        $this->errorHandler = new _ChargeBeeHandleErrors();
    }

    public function postHostedPages(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $send = $this->hostedPages($body);

        return $response->withJson($send, $send['status']);
    }

    public function hostedPages($body)
    {
        $hp = function ($body) {
            return \ChargeBee_HostedPage::checkoutNew($body)->hostedPage()->getValues();
        };

        return $this->errorHandler->handleErrors($hp, $body);
    }
}
