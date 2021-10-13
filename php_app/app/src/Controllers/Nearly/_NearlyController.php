<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 04/01/2017
 * Time: 18:53
 */

namespace App\Controllers\Nearly;

use App\Controllers\Billing\Subscriptions\FailedTransactionController;
use App\Controllers\Branding\_BrandingController;
use App\Controllers\Clients\Payments\_ClientPaymentsController;
use App\Controllers\Integrations\DDNS\DDNS;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\PayPal\_PayPalController;
use App\Controllers\Integrations\S3\S3;
use App\Controllers\Integrations\Stripe\_StripeCardsController;
use App\Controllers\Integrations\Stripe\_StripeCustomerController;
use App\Controllers\Locations\Pricing\_LocationPlanController;
use App\Controllers\Locations\Settings\_LocationScheduleController;
use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Controllers\Locations\Settings\Policy\LocationPolicyController;
use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Controllers\Locations\Settings\Social\LocationFacebookController;
use App\Controllers\Nearly\Stories\NearlyStoryController;
use App\Controllers\Registrations\_RegistrationsController;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\LocationSettings;
use App\Models\Nearly\Impressions;
use App\Models\Nearly\ImpressionsAggregate;
use App\Models\Nearly\Stories\NearlyStoryPageActivity;
use App\Models\User\UserBlocked;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Package\Nearly\NearlyInput;
use App\Package\Nearly\NearlyOutput;
use App\Package\Nearly\NearlyProfile;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;

class _NearlyController
{

    protected $em;
    protected $s3;
    protected $nearlyCache;
    private $questionsValueKeys = [
        'Email'     => 'email',
        'Firstname' => 'first',
        'Lastname'  => 'last',
        'Phone'     => 'phone',
        'Postcode'  => 'postcode',
        'Optin'     => 'opt',
        'Gender'    => 'gender',
        'Country'   => 'country'
    ];

    private $paidQuestionsKeys = [
        'Email'     => 'email',
        'Firstname' => 'first',
        'Lastname'  => 'last',
        'Phone'     => 'phone'
    ];
    /**
     * @var Logger
     */
    private $logger;

    /**
     * _NearlyController constructor.
     * @param Logger $logger
     * @param EntityManager $em
     */

    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->logger = $logger;
        $this->em          = $em;
        $this->s3          = new S3('nearly.online', 'eu-west-1');
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    public function settingsAll(Request $request, Response $response)
    {

        $nearlyInput = new NearlyInput();
        $params = $nearlyInput->createFromArray($request->getQueryParams());

        if (!$params->getMac() || !$params->getSerial()) {
            return $response->withStatus(400);
        }


        $ipOfDevice = $request->getHeader('X-Forwarded-For');
        if (is_array($ipOfDevice) && !empty($ipOfDevice)) {
            $ipOfDevice = $ipOfDevice[0];
        }
        if (is_string($ipOfDevice) && stripos($ipOfDevice, ',') !== false) {
            $mutipleIps = explode(',', $ipOfDevice);
            if (count($mutipleIps) >= 2) {
                $ipOfDevice = $mutipleIps[0];
            }
        } else {
            if (is_null($ipOfDevice) || empty($ipOfDevice)) {
                $ipOfDevice = $_SERVER['REMOTE_ADDR'];
            }
        }

        $results = $this->getAllSettings($params, $ipOfDevice);
        if (!is_null($results)) {
            return $response->withJson($results);
        }
        return $response->withJson($results);
    }

    public function getAllSettings(
        NearlyInput $input,
        string $ipOfDevice
    ): ?NearlyOutput {

        $serial = $input->getSerial();
        /**
         * @var LocationSettings $location
         */
        $location = $this->em->getRepository(LocationSettings::class)->findOneBy(['serial' => $serial]);

        if (is_null($location)) {
            return null;
        }

        $nearlyProfile = new NearlyProfile($this->em);
        $profile = $nearlyProfile->getProfileFromMac($input);
        $output = new NearlyOutput($location, $profile);


        if (!is_null($profile)) {
            $userBlocked = $this->em->createQueryBuilder()
                ->select('u.id')
                ->from(UserBlocked::class, 'u')
                ->where('u.serial = :serial')
                ->andWhere('u.mac = :mac')
                ->setParameter('serial', $serial)
                ->setParameter('mac', $input->getMac())
                ->getQuery()
                ->getArrayResult();

            if (!empty($userBlocked)) {
                $output->setBlocked(true);
                return $output;
            }
        }

        $newBrandingController = new BrandingController($this->em);
        $newBrandingController->parseNearlyBranding($location->getBrandingSettings(), $location->getSerial());
        $devicesController     = new _NearlyDevicesController($this->logger, $this->em);

        // $location                     = $locationController->getNearlyLocation($serial, $res[0]['location'])['message'];

        if (!$input->getPreview()) {
            if ($location->getOtherSettings()->getDdnsEnabled()) {
                $ddns = new DDNS($ipOfDevice, $serial);
                $ddns->save();
            }
        }

        //They may not have background image set
        /**
         *Location types
         * 0 = FREE
         * 1 = PAID
         * 2 = HYBRID
         */
        if ($location->getType() !== 0) {
            if ($location->getType() === 2 && is_null($profile)) {
                $location->setType(0);
            } elseif ($location->getType() === 2) {
                $checkLimit = $devicesController->checkDataUsage(
                    $profile->getId(),
                    $serial,
                    $location->getOtherSettings()->getHybridLimit()
                );

                if ($checkLimit['status'] == 402) {
                    $location->setType(1);
                } else {
                    $location->setType(0);
                }
            }
            if ($location->getType() === 1) {
                $planController = new _LocationPlanController($this->em);
                $plans          = $planController->receiveAllPlansFromSerial($serial);
                $output->setPaymentPlans($plans['message']);
                if (!is_null($profile)) {
                    $devicesController = new _NearlyDevicesController($this->logger, $this->em);
                    $devices = $devicesController->paidDevices($profile->getId(), $serial);

                    if ($devices['message']['device_count'] >= 1) {
                        $clientTransactionsController = new _ClientPaymentsController($this->em);
                        $transactions                 = $clientTransactionsController->getTransactions(
                            $profile->getId(),
                            $serial
                        );
                        if ($transactions['status'] === 200) {
                            $output->setPaymentDevices($devices['message']['devices']);
                            $output->setPaymentTransactions($transactions['message']);
                        }
                    }

                    if ($location->getUsingStripe()) {
                        $stripeCustomerController = new _StripeCustomerController($this->logger, $this->em);
                        $stripeCustomer           = $stripeCustomerController->createCustomer(
                            $profile->getId(),
                            $serial,
                            null
                        );
                        if ($stripeCustomer['status'] === 200) {
                            $output->setStripeCustomerId($stripeCustomer['message']['stripeCustomerId']);
                            $newCardsController = new _StripeCardsController($this->logger, $this->em);
                            $cardsRequest       = $newCardsController->stripeListCards(
                                $stripeCustomer['message']['stripe_user_id'],
                                $stripeCustomer['message']['stripeCustomerId']
                            );

                            if ($cardsRequest['status'] === 200) {
                                if (is_array($cardsRequest['message']->data)) {
                                    $output->setStripePaymentMethods($cardsRequest['message']->data);
                                }
                            }
                        }
                    }
                }
            }
        }




        /*
            if ($send['location']['type'] === 'free') {
                $sessionAuth             = $devicesController->checkAuth($serial, $mac);
                $send['session']['auth'] = $sessionAuth['message']['auth'];
            }
*/
        /*
            if (($send['location']['type'] === 'paid' || $send['location']['type'] === 'hybrid')) {

                if ($send['paymentSettings']['stripe'] === true) {

                    if ($stripeCustomer['status'] === 200) {

                        $newCardsController = new _StripeCardsController($this->logger, $this->em);
                        $cardsRequest       = $newCardsController->stripeListCards(
                            $stripeCustomer['message']['stripe_user_id'],
                            $stripeCustomer['message']['stripeCustomerId']
                        );

                        if ($cardsRequest['status'] === 200) {
                            if (!is_array($cardsRequest['message']->data)) {
                                $send['profile']['cards'] = [];
                            } else {
                                foreach ($cardsRequest['message']->data as $card) {
                                    $card['default'] = false;
                                    if ($cardsRequest['message']->default_source === $card->id) {
                                        $card['default'] = true;
                                    }
                                    $send['profile']['cards'][] = $card;
                                }
                            }
                        }
                    }
                }
            }
*/



        if (!$input->getPreview() && !$output->getBlocked()) {
            $impression = new Impressions(is_null($profile) ? null : $profile->getId(), $serial);
            $this->em->persist($impression);

            $output->setImpressionId($impression->id);
            $hour  = date('H');
            $day   = date('j');
            $week  = date('W');
            $month = date('m');
            $year  = date('Y');

            $date      = new \DateTime();
            $formatted = $date->format('Y-m-d H:00:00');

            $date = new \DateTime($formatted);

            $impressionAggregateExists = $this->em->getRepository(ImpressionsAggregate::class)->findOneBy([
                'serial' => $serial,
                'hour'   => $hour,
                'week'   => $week,
                'day'    => $day,
                'month'  => $month,
                'year'   => $year
            ]);

            if (is_null($impressionAggregateExists)) {
                $newAggregate              = new ImpressionsAggregate(
                    $serial,
                    $year,
                    $month,
                    $week,
                    $day,
                    $hour,
                    $date
                );
                $newAggregate->impressions += 1;
                $this->em->persist($newAggregate);
            } else {
                $impressionAggregateExists->impressions += 1;
            }


            if (!is_null($profile)) {
                $nearlyStoryController = new NearlyStoryController($this->em);
                $nearlyStory                  = $nearlyStoryController->get($serial);
                if ($nearlyStory['status'] === 200) {
                    if ($nearlyStory['message']['enabled'] === true) {
                        foreach ($nearlyStory['message']['pages'] as $key => $page) {
                            $newActivity = new NearlyStoryPageActivity(
                                $page['pageId'],
                                $profile->getId(),
                                $serial
                            );
                            $this->em->persist($newActivity);
                            $nearlyStory['message']['pages'][$key]['trackingId'] = $newActivity->id;
                        }
                        $output->setStoryEnabled(true);
                        $output->setStoryPages($nearlyStory['message']);
                    }
                }
            }
        }

        //If user has more to do and the location is in good standing, create the session.

        if ($output->shouldAutoAuth()) {
            $newValidation     = new NearlyAuthController($this->em);
            $validationPayload = $newValidation->createSession([
                'email'     => $profile->getEmail(),
                'mac'       => $input->getMac(),
                'ip'        => $input->getIp(),
                'serial'    => $serial,
                'type'      => $location->getType(),
                'id'        => $profile->getId(),
                'ap'        => $input->getAp(),
                'port'      => $input->getPort(),
                'challenge' => $input->getChallenge(),
                'auth_time' => 0,
            ]);

            if (isset($validationPayload['status'])) {
                if ($validationPayload['status'] === 409) {
                    return Http::status($validationPayload['status'], $validationPayload['message']);
                }
            }
            $output->setAuthPayload($validationPayload);
        }

        $this->em->flush();

        return $output;
    }

    public function paypalSettings(string $serial)
    {
        $newPayPalController = new _PayPalController($this->em);
        $locationAccount     = $newPayPalController->getFromSerial($serial);
        $payPalAccountId     = $locationAccount->paypalAccount;

        return $newPayPalController->retrieveAccount($payPalAccountId, $serial);
    }
}
