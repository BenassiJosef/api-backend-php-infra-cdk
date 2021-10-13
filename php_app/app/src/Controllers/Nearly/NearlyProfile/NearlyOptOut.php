<?php
/**
 * Created by jamieaitken on 03/05/2018 at 09:50
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile;

use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Package\Domains\Registration\UserRegistrationRepository;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;

class NearlyOptOut
{
    protected $em;
    protected $userRegistrationRepository;
    /**
     * @var Logger
     */
    private $logger;
    private $queueSender;

    /**
     * NearlyOptOut constructor.
     * @param Logger $logger
     * @param EntityManager $em
     * @param UserRegistrationRepository $userRegistrationRepository
     */
    public function __construct(Logger $logger, EntityManager $em, UserRegistrationRepository $userRegistrationRepository, QueueSender $queueSender)
    {
        $this->em = $em;
        $this->userRegistrationRepository = $userRegistrationRepository;
        $this->logger = $logger;
        $this->queueSender = $queueSender;
    }

    public function optOutRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $send = $this->optOut($request->getAttribute('nearlyUser')['profileId'], $body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function optOut(string $id, array $body)
    {
        if (!isset($body['serial'])) {
            return Http::status(400, 'NO_SERIAL');
        }

        $serial = $body['serial'];
        $locationOptOut = false;

        if (isset($body['data'])) {
            $currentLocationOptOut = $this->em->getRepository(LocationOptOut::class)->findOneBy([
                'profileId' => $id,
                'serial'    => $serial
            ]);
            $locationOptOut = $body['data'] === true;

            if (is_null($currentLocationOptOut) && $locationOptOut) {
                // create a new location opt out for the user
                $newOptOut = new LocationOptOut($id, $serial);
                $this->em->persist($newOptOut);
            } else {
                // make sure the existing location opt out deleted flag is correct (deleted = true for opt in, false for opt out)
                $currentLocationOptOut->deleted = !$locationOptOut;
                $this->em->persist($currentLocationOptOut);
            }
            $this->userRegistrationRepository->updateLocationOptOut($id, $serial, $locationOptOut);
        }

        // if the user has specified some marketing options or they have opted out of the location then we need to update the marketing opt outs
        if (isset($body['marketing']) || $locationOptOut === true) {
            $currentSmsOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy([
                'uid'    => $id,
                'serial' => $serial,
                'type'   => 'sms'
            ]);

            $currentEmailOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy([
                'uid'    => $id,
                'serial' => $serial,
                'type'   => 'email'
            ]);

            // work out the new values for opt in and out - has it been specified by the user or do we use the location opt out?
            $smsOptOut = isset($body['marketing']) ? $body['marketing']['sms'] : $locationOptOut;
            $emailOptOut = isset($body['marketing']) ? $body['marketing']['email'] : $locationOptOut;

            if (is_null($currentSmsOptOut)) {
                $newMarketingSMS         = new MarketingOptOut($id, $serial, 'sms');
                $newMarketingSMS->optOut = $smsOptOut;
                $this->em->persist($newMarketingSMS);
            } else {
                $currentSmsOptOut->optOut = $smsOptOut;
                $this->em->persist($currentSmsOptOut);
            }

            if (is_null($currentEmailOptOut)) {
                $newMarketingEmail         = new MarketingOptOut($id, $serial, 'email');
                $newMarketingEmail->optOut = $emailOptOut;
                $this->em->persist($newMarketingEmail);
            } else {
                $currentEmailOptOut->optOut = $body['marketing']['email'];
                $this->em->persist($currentEmailOptOut);
            }
            $this->userRegistrationRepository->updateMarketingOptOuts($id, $serial, $emailOptOut, $smsOptOut);

            // inform relevant people thay the user needs to be removed from any mailing lists
            if (!$locationOptOut && $body['marketing']['email'] === true) {

                $this->queueSender->sendMessage([
                    'profileId' => $id,
                    'serial'    => $serial,
                ], QueueUrls::GDPR_NOTIFIER);
            }
        }

        $this->em->flush();

        return Http::status(200);
    }
}