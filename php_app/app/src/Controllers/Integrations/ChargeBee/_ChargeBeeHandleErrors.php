<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 30/06/2017
 * Time: 14:22
 */

namespace App\Controllers\Integrations\ChargeBee;

use App\Utils\Http;

class _ChargeBeeHandleErrors
{
    public function handleErrors($request, $requestParameters)
    {
        try {
            _ChargeBeeAuth::initilize();
            $res = $request($requestParameters);
        } catch (\ChargeBee_PaymentException $e) {
            return Http::status($e->getHttpStatusCode(), $e->getMessage());
        } catch (\ChargeBee_InvalidRequestException $e) {
            return Http::status($e->getHttpStatusCode(), $e->getMessage());
        } catch (\ChargeBee_OperationFailedException $e) {
            return Http::status($e->getHttpStatusCode(), $e->getMessage());
        } catch (\ChargeBee_APIError $e) {
            return Http::status($e->getHttpStatusCode(), $e->getMessage());
        } catch (\ChargeBee_IOException $e) {
            return Http::status($e->getCurlErrorCode(), $e->getMessage());
        } catch (\Exception $e) {
            return Http::status(500, $e->getMessage());
        }

        return Http::status(200, $res);
    }
}
