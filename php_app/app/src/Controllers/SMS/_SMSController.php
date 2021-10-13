<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 15/06/2017
 * Time: 18:12
 */

namespace App\Controllers\SMS;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Marketing\_MarketingCallBackController;
use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Plivo\RestAPI;
use Plivo\RestClient;
use Slim\Http\Response;
use Slim\Http\Request;
use Twilio\Rest\Client;

class _SMSController
{

    protected $validationService = 'TWILIO';

    protected $sendService = 'TWILIO';

    protected $twilioAuthId = 'AC2374c1d9d55c9e38b5ef4b2632e22d8b';

    protected $twilioAuthToken = '66eac95910eff246b4ce71569381b6f4';

    protected $twilioServiceId = 'VA3acc540e3c4093d2680539f11e226e0f';

    protected $plivoAccountId = 'MANZU1YZI4OTNLZDMZND';

    protected $plivoAuthToken = 'MzlhMzYwNWI4ODc1NTBiYmZmMTQ2NTg3MzZlYzVi';

    protected $optOutNumber = '+447441915123';

    protected $em;

    protected $mp;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->mp = new _Mixpanel();
    }

    public function sendRoute(Request $request, Response $response)
    {
        $send = $this->send($request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function countryRoute(Request $request, Response $response)
    {
    }

    public function verifyRoute(Request $request, Response $response)
    {
        $params = $request->getParsedBody();
        if (!isset($params['phone'])) {
            $send = [
                'message' => 'NO_PHONE_NUMBER',
                'status'  => 204
            ];
        } else {
            $send = $this->sendVerification($params['phone']);
        }


        return $response->withJson($send, $send['status']);
    }

    public function checkVerifyRoute(Request $request, Response $response)
    {
        $params = $request->getParsedBody();
        if (!isset($params['phone']) || !isset($params['code'])) {
            $send = [
                'message' => 'NO_PHONE_OR_CODE',
                'status'  => 204
            ];
        } else {
            $send = $this->checkVerificationCode($params['code'], $params['phone']);
        }

        return $response->withJson($send, $send['status']);
    }

    public function validateRoute(Request $request, Response $response)
    {

        $params = $request->getQueryParams();
        if (!isset($params['phone'])) {
            $send = [
                'message' => 'NO_PHONE_NUMBER',
                'status'  => 204
            ];
        } else {

            $send = $this->validate($params['phone']);
        }

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function sendVerification(string $phone)
    {
        $twilio       = new Client($this->twilioAuthId, $this->twilioAuthToken);
        $verification = $twilio->verify->v2->services($this->twilioServiceId)
            ->verifications
            ->create($phone, "sms");

        return Http::status(200, $verification);
    }

    public function checkVerificationCode(string $code, string $phone)
    {
        $twilio             = new Client($this->twilioAuthId, $this->twilioAuthToken);
        $verification_check = $twilio->verify->v2->services($this->twilioServiceId)
            ->verificationChecks
            ->create(
                $code, // code
                ["to" => $phone]
            );

        return Http::status($verification_check->valid === true ? 200 : 204, $verification_check->status);
    }

    public function send(array $body)
    {
        $requiredParams = [
            'number',
            'message',
            'sender'
        ];
        $validation     = Validation::pastRouteBodyCheck($body, $requiredParams);
        if (is_array($validation)) {
            return Http::status(400, 'REQUIRES' . '_' . strtoupper(implode('_', $validation)));
        }
        $message     = $body['message'];
        $destination = $body['number'];
        $sender      = $body['sender'];
        $destination = str_replace('??', '', $destination);
        $sender      = strlen($sender) > 11 ? substr($sender, 0, 11) : $sender;

        if (strpos($destination, '4407') !== false) {
            $destination = str_replace('4407', '447', $destination);
        }

        $data                 = null;
        $newMarketingCallback = new _MarketingCallBackController($this->em);

        if (strlen($message) <= 133) {
            $message = $message . ' TXT ' . $body['optOutCode'] . ' 2 ' . $this->optOutNumber;
        }

        switch ($this->sendService) {
            case 'TWILIO':
                $newTwilioService = new Client($this->twilioAuthId, $this->twilioAuthToken);
                $newMessage       = $newTwilioService->messages->create(
                    $destination,
                    [
                        'from' => $sender,
                        'body' => $message
                    ]
                );

                if ($newMessage->status === 'failed' || $newMessage->status === 'undelivered') {
                    $data['status']   = $newMessage->errorCode;
                    $data['response'] = $newMessage->errorMessage;
                } else {
                    $data['status']   = 200;
                    $data['response'] = $newMessage->status;
                }

                $newMarketingCallback->insertSmsCallBack([
                    'MessageUUID'       => $newMessage->sid,
                    'ParentMessageUUID' => $newMessage->sid,
                    'To'                => $destination,
                    'Status'            => $newMessage->status,
                    'serial'            => $body['serial'],
                    'profileId'         => $body['profileId']
                ]);

                break;
            case 'PLIVO':
                $newPlivioService = new RestClient($this->plivoAccountId, $this->plivoAuthToken);
                $data             = $newPlivioService->message->create(
                    $sender,
                    [$destination],
                    $message
                );

                $this->mp->track('SMS_BEFORE_CALLBACK', [
                    'data' => $data
                ]);

                if ($data['error_code'] !== 400) {
                    $newMarketingCallback->insertSmsCallBack([
                        'MessageUUID'       => $data['message_uuid'],
                        'ParentMessageUUID' => $data['message_uuid'],
                        'To'                => $destination,
                        'Status'            => $data['state'],
                        'serial'            => $body['serial'],
                        'profileId'         => $body['profileId']
                    ]);
                }
                break;
        }

        $this->mp->track('REQUEST_SMS_TO_BE_SENT', [
            'status'   => $data['status'],
            'response' => $data['response']
        ]);

        return Http::status($data['status'], $data['response']);
    }

    public function validate(string $phone)
    {
        $data = null;
        switch ($this->validationService) {
            case 'TWILIO':
                $newTwilioService = new Client($this->twilioAuthId, $this->twilioAuthToken);
                try {
                    $newLookUp = $newTwilioService->lookups->phoneNumbers($phone)->fetch([
                        'type' => 'carrier'
                    ]);
                } catch (\Twilio\Exceptions\RestException $exception) {
                    $data['status']  = $exception->getStatusCode();
                    $data['message'] = $exception->getMessage();

                    break;
                }

                $data['status'] = 200;

                $data['message'] = [
                    'countryCode' => $newLookUp->countryCode,
                    'phoneNumber' => $newLookUp->phoneNumber
                ];

                break;
        }

        return Http::status($data['status'], $data['message']);
    }
}
