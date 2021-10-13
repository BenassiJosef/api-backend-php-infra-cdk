<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 17/10/2017
 * Time: 10:54
 */

namespace App\Controllers\Marketing\Campaign;

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\SMS\RandomOptOutCodeGenerator;
use App\Models\Locations\Marketing\MarketingMessagePreview;
use App\Models\MarketingMessages;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _PreviewCampaignController
{
    protected $em;
    protected $mail;
    protected $marketingCache;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->marketingCache = new CacheEngine(getenv('MARKETING_REDIS'));
        $this->mail = new _MailController($this->em);
    }

    public function sendTestMessageRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $user = $request->getAttribute('user');
        $send = $this->sendTestMessage($body, $user);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function sendTestMessage(array $body, array $user)
    {
        $sentBefore = $this->em->getRepository(MarketingMessagePreview::class)->findOneBy([
            'id' => $body['messageId']
        ]);

        if (is_null($sentBefore)) {
            $sentBefore = new MarketingMessagePreview($body['messageId']);
            $this->em->persist($sentBefore);
            $this->em->flush();
        }

        if ($sentBefore->sent >= 3) {
            $testMessage = $this->sendPreview(
                $body['messageId'], 
                $body['to'],
                $user['uid'],
                $body['type']
            );
            if ($testMessage['status'] !== 200) {
                return Http::status($testMessage['status'], $testMessage['message']);
            }
        } else {
            $testMessage = $this->sendPreview(
                $body['messageId'],
                $body['to'],
                $user['uid'],
                $body['type']
            );
            if ($testMessage['status'] !== 200) {
                return Http::status($testMessage['status'], $testMessage['message']);
            }
        }
        $sentBefore->sent += 1;
        $this->em->flush();

        return Http::status(200);
    }

    public function sendPreview(
        string $messageId,
        array $to,
        string $adminId,
        $type = false
    ) {
        $message = $this->em->getRepository(MarketingMessages::class)->findOneBy([
            'id' => $messageId
        ]);

        $publisher = new QueueSender();

        if ($type === false) {
            if ($message->sendToSms === true) {
                $type = 'sms';
            }
            if ($message->sendToEmail === true) {
                $type = 'email';
            }
            if ($message->sendToSms === true && $message->sendToEmail === true) {
                $type = 'both';
            }
        }


        if ($type === 'sms' || $type === 'both') {

            if (!isset($to['phone'])) {
                return Http::status(404, 'SMS_MESSAGES_REQUIRE_PHONE_NUMBER');
            }

            $optOutCode = RandomOptOutCodeGenerator::getCode();

            $publisher->sendMessage([
                'number' => $to['phone']['number'],
                'message' => $message->smsContents,
                'sender' => $message->smsSender,
                'messageId' => $message->getid(),
                'serial' => 'PREVIEW',
                'profileId' => 0,
                'campaignId' => 'PREVIEW',
                'campaignAdmin' => $message->admin,
                'eventId' => 'PREVIEW',
                'deduct' => false,
                'optOutCode' => $optOutCode
            ], QueueUrls::SMS_DELIVERY);
        }
        if ($type === 'email' || $type === 'both') {
            if (isset($to['email'])) {
                if (!isset($to['email']['emailAddress'])) {
                    return Http::status(404, 'EMAIL_MESSAGES_REQUIRE_EMAIL_ADDRESS');
                }

                if (!isset($to['email']['name'])) {
                    return Http::status(404, 'EMAIL_MESSAGES_REQUIRE_NAME');
                }

                if (is_null($message->subject)) {
                    return Http::status(409, 'SUBJECT_HAS_NOT_BEEN_SET');
                }

                $this->marketingCache->save('campaignMessages:' . $messageId, [
                    'message' => [
                        'emailContents' => $message->emailContents,
                        'templateType' => $message->templateType
                    ]
                ]);

                $profile = [
                    'first' => 'Jane',
                    'last' => 'Doe',
                    'email' => 'jane.doe@stampede.ai',
                    'company' => 'Stampede'
                ];
                /* Testing Local
                                $messageArray = $this->mail->preparePlainStructure($messageId, $message->emailContents,
                                    $message->templateType, $profile);

                                $messageArray = array_merge([
                                    'serial' => 'PREVIEW',
                                    'uid' => 0,
                                    'templateType' => $message->templateType,
                                    'campaignId' => 'PREVIEW'
                                ], $messageArray);

                                 $this->mail->send(
                                    [
                                        [
                                            'to' => $to['email']['emailAddress'],
                                            'name' => $to['email']['name']
                                        ]
                                    ],
                                    $messageArray,
                                    'MarketingHTMLTemplate',
                                    $message->subject
                                );

                                return Http::status(200);
                */

                $publisher->sendMessage([
                    'profile' => $profile,
                    'to' => $to['email']['emailAddress'],
                    'name' => $to['email']['name'],
                    'serial' => 'PREVIEW',
                    'uid' => 0,
                    'templateType' => $message->templateType,
                    'campaignId' => 'PREVIEW',
                    'campaignAdmin' => $message->admin,
                    'eventId' => 'PREVIEW',
                    'subject' => $message->subject,
                    'messageId' => $message->getid(),
                    'deduct' => false,
                    'profileId' => 0
                ], QueueUrls::EMAIL_DELIVERY);
            }
        }

        return Http::status(200);
    }
}
