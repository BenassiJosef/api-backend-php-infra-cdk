<?php

/**
 * Created by jamieaitken on 17/04/2019 at 10:29
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations;


use App\Controllers\Integrations\Airship\AirshipSetupController;
use App\Controllers\Integrations\CampaignMonitor\CampaignMonitorSetupController;
use App\Controllers\Integrations\dotMailer\DotMailerSetupController;
use App\Controllers\Integrations\Hooks\_HooksController;
use App\Controllers\Integrations\MailChimp\MailChimpSetupController;


use App\Controllers\Integrations\PayPal\_PayPalController;
use App\Controllers\Integrations\Stripe\_StripeController;
use App\Controllers\Integrations\Stripe\_StripeCustomerController;
use App\Controllers\Integrations\Textlocal\TextLocalSetupController;
use App\Controllers\Integrations\UniFi\_UniFiController;
use App\Models\StripeConnect;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;
use Stripe\Stripe;

class ConnectedIntegrationController
{
    protected $em;

    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->logger = $logger;
        $this->em     = $em;
    }

    public function isConnectedRoute(Request $request, Response $response)
    {

        $send = $this->isConnected($request->getAttribute('orgId'));

        return $response->withJson($send, $send['status']);
    }

    public function isConnected(string $orgId)
    {
        $returnStructure = [
            'paypal'          => false,
            'unifi'           => false,
            'stripe'          => false,
            'mailchimp'       => false,
            'dotmailer'       => false,
            'zapier'          => false,
            'textlocal'       => false,
            'campaignMonitor' => false,
            'airship'         => false
        ];

        $paypal                             = new _PayPalController($this->em);
        $unifi                              = new _UniFiController($this->em);
        $stripe                             = new _StripeController($this->logger, $this->em);
        $mailchimp                          = new MailChimpSetupController($this->em);
        $dotmailer                          = new DotMailerSetupController($this->em);
        $campaignMonitor                    = new CampaignMonitorSetupController($this->em);
        $textLocal                          = new TextLocalSetupController($this->em);
        $zapier                             = new _HooksController($this->em);
        $airship                            = new AirshipSetupController($this->em);
        $paypalAccounts                     = $paypal->retrieveAccounts($orgId);
        $unifiAccounts                      = $unifi->getUsersControllers($orgId);
        $stripeAccounts                     = $stripe->getAllByID($orgId);
        $mailchimpAccount                   = $mailchimp->getUserDetails($orgId);
        $dotmailerAccount                   = $dotmailer->getUserDetails($orgId);
        $textLocalAccount                   = $textLocal->getUserDetails($orgId);
        $campaignMonitorAccount             = $campaignMonitor->getUserDetails($orgId);
        $zapierAccount                      = $zapier->getAll($orgId);
        $airshipAccount                     = $airship->getUserDetails($orgId);
        $returnStructure['paypal']          = $paypalAccounts['status'] === 200 ? true : false;
        $returnStructure['unifi']           = $unifiAccounts['status'] === 200 ? true : false;
        $returnStructure['stripe']          = !empty($stripeAccounts['message']) && $stripeAccounts['status'] === 200 ?
            true : false;
        $returnStructure['mailchimp']       = $mailchimpAccount['status'] === 200 ? true : false;
        $returnStructure['dotmailer']       = $dotmailerAccount['status'] === 200 ? true : false;
        $returnStructure['textlocal']       = $textLocalAccount['status'] === 200 ? true : false;
        $returnStructure['campaignMonitor'] = $campaignMonitorAccount['status'] === 200 ? true : false;
        $returnStructure['zapier']          = !empty($zapierAccount['message']) ? true : false;
        $returnStructure['airship']         = $airshipAccount['status'] === 200 ? true : false;

        return Http::status(200, $returnStructure);
    }
}
