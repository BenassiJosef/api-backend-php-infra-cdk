<?php

/**
 * Created by jamieaitken on 20/02/2018 at 16:06
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly;


use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Models\Auth\Provider;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\Device\DeviceBrowser;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\LocationOptOut;
use App\Models\Locations\LocationSettings;
use App\Models\Marketing\MarketingOptOut;
use App\Models\MarketingEvents;
use App\Models\NetworkAccess;
use App\Models\OauthUser;
use App\Models\User\UserAgent;
use App\Models\User\UserDevice;
use App\Models\UserData;
use App\Models\UserPayments;
use App\Models\UserProfile;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class NearlyProfileController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function organisationOptOut(Request $request, Response $response)
    {
        $profileId = $request->getAttribute('profileId');
        $organizationId = $request->getParsedBodyParam('organization_id');
        $dataOptIn = $request->getParsedBodyParam('data_opt_in');
        $emailOptIn = $request->getParsedBodyParam('email_opt_in');
        $smsOptIn = $request->getParsedBodyParam('sms_opt_in');

        /**
         * @var OrganizationRegistration $organisation
         */
        $organisation = $this->em->getRepository(OrganizationRegistration::class)->findOneBy([
            'profileId' => $profileId,
            'organizationId' => $organizationId
        ]);

        if (is_null($organisation)) {
            return $response->withJson('ORGANIZATION_NOT_FOUND', 404);
        }

        $organisation->setEmailOptIn($emailOptIn);
        $organisation->setSmsOptIn($smsOptIn);
        $organisation->setDataOptIn($dataOptIn);

        $this->em->persist($organisation);
        $this->em->flush();

        return $response->withJson($organisation->jsonSerialize(), 200);
    }


    public function loadOrganisationsRoute(Request $request, Response $response)
    {
        $profileId = $request->getAttribute('profileId');
        /**
         * @var OrganizationRegistration[] $organisations
         */
        $organisations = $this->em->getRepository(OrganizationRegistration::class)->findBy(['profileId' => $profileId]);
        $res = [];
        foreach ($organisations as $organisation) {
            $res[] = $organisation->jsonSerialize();
        }


        return $response->withJson($res, 200);
    }

    public function loadProfileRoute(Request $request, Response $response)
    {
        $send = $this->loadProfile($request->getAttribute('nearlyUser')['profileId'], []);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }


    public function deleteAccountRoute(Request $request, Response $response)
    {
        $send = $this->deleteAccount($request->getAttribute('nearlyUser')['profileId']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteAccount(string $id)
    {

        $getProfile = $this->em->createQueryBuilder()
            ->select('
            u.id,
            u.email,
            u.first,
            u.last,
            u.phone,
            u.postcode,
            u.age,
            u.birthMonth,
            u.birthDay,
            u.gender,
            u.lat,
            u.lng,
            u.country
            ')
            ->from(UserProfile::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        $getAllSerialsThatProfileHasBeenTo = $this->em->createQueryBuilder()
            ->select('u.serial, ls.alias')
            ->from(UserData::class, 'u')
            ->leftJoin(LocationSettings::class, 'ls', 'WITH', 'u.serial = ls.serial')
            ->where('u.profileId = :id')
            ->setParameter('id', $id)
            ->groupBy('u.serial')
            ->getQuery()
            ->getArrayResult();

        $alias   = [];
        $serials = [];

        foreach ($getAllSerialsThatProfileHasBeenTo as $value) {
            $alias[$value['serial']] = $value['alias'];
            $serials[]               = $value['serial'];
        }

        $admins = $this->em->createQueryBuilder()
            ->select('o.email, o.first, o.last')
            ->from(NetworkAccess::class, 'u')
            ->leftJoin(OauthUser::class, 'o', 'WITH', 'u.admin = o.uid')
            ->where('u.serial IN (:serial)')
            ->setParameter('serial', $serials)
            ->getQuery()
            ->getArrayResult();


        $newMail  = new _MailController($this->em);
        $mixPanel = new _Mixpanel();

        $customer = [
            'id'         => $getProfile[0]['id'],
            'email'      => $getProfile[0]['email'],
            'first'      => $getProfile[0]['first'],
            'last'       => $getProfile[0]['last'],
            'phone'      => $getProfile[0]['phone'],
            'postcode'   => $getProfile[0]['postcode'],
            'age'        => $getProfile[0]['age'],
            'birthMonth' => $getProfile[0]['birthMonth'],
            'birthDay'   => $getProfile[0]['birthDay'],
            'gender'     => $getProfile[0]['gender'],
            'lat'        => $getProfile[0]['lat'],
            'lng'        => $getProfile[0]['lng'],
            'country'    => $getProfile[0]['country']
        ];

        $newMail->send(
            [
                [
                    'to'   => 'support@stampede.ai',
                    'name' => 'Stampede Support'
                ]
            ],
            [
                'customer' => $customer
            ],
            'RemoveData',
            'GDPR: Customer Requested Data Removal'
        );


        /*foreach ($admins as $key => $admin) {
            $send = $newMail->send(
                [
                    [
                        'to'   => $admin['email'],
                        'name' => $admin['first'] . ' ' . $admin['last']
                    ]
                ]
                ,
                [
                    'customer' => $customer,
                    'serial'   => $admin['serial'],
                    'alias'    => $alias[$admin['serial']]
                ],
                'RemoveData',
                'GDPR: Customer Requested Data Removal');

            if ($send['status'] !== 200) {
                $mixPanel->track('FAILED_TO_FULFILL_GDPR_DELETION_REQUEST', [
                    'admin'       => [
                        'name'  => $admin['first'] . ' ' . $admin['last'],
                        'email' => $admin['email']
                    ],
                    'dataSubject' => [
                        'id'    => $getProfile[0]['id'],
                        'email' => $getProfile[0]['email']
                    ]
                ]);
            }
        }*/

        $mixPanel->track('GDPR_DELETION_REQUEST', [
            'dataSubject' => [
                'id'    => $getProfile[0]['id'],
                'email' => $getProfile[0]['email']
            ]
        ]);

        return Http::status(200, 'GDPR_DELETION_REQUEST');
    }

    public function loadProfile(string $id, array $serials)
    {

        $profileStructure = [
            'id'            => $id,
            'name'          => '',
            'fullName'      => [
                'first' => '',
                'last'  => ''
            ],
            'email'         => '',
            'gender'        => '',
            'phone'         => '',
            'postcode'      => '',
            'postcodeValid' => false,
            'avatar'        => [],
            'age'           => '',
            'birthMonth'    => '',
            'birthDay'      => '',
            'verified'      => '',
            'locations'     => [
                'list'   => [],
                'totals' => [
                    'download' => 0,
                    'upload'   => 0,
                    'logins'   => 0,
                    'uptime'   => 0,
                    'payments' => [
                        'sum'   => 0,
                        'count' => 0
                    ]
                ]
            ],
            'devices'       => [],
            'marketing'     => [
                'locations' => [],
                'totals'    => [
                    'email' => 0,
                    'sms'   => 0
                ]
            ]
        ];

        $userData = $this->userProfile($id);

        if (empty($userData)) {
            return Http::status(404, 'NOT_A_VALID_USER');
        }

        foreach ($userData as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if ($key === 'first' || $key === 'last') {

                $profileStructure['fullName'][$key] = $value;

                $profileStructure['name'] .= $value . ' ';
            }

            if (isset($profileStructure[$key])) {
                $profileStructure[$key] = $value;
            }
        }

        $connections = $this->userConnections($id, $serials);

        if (!empty($connections)) {
            foreach ($connections as $key => $connection) {

                if (!isset($profileStructure['locations']['list'][$connection['serial']])) {
                    $profileStructure['locations']['list'][$connection['serial']] = [
                        'totalDownload' => 0,
                        'totalUpload'   => 0,
                        'uptime'        => 0,
                        'logins'        => 0,
                        'name'          => $connection['alias'],
                        'logo'          => $connection['logo'],
                        'payments'      => [
                            'count'          => 0,
                            'sum'            => 0,
                            'actualPayments' => []
                        ],
                        'optedOut'      => [
                            'locationData'  => false,
                            'marketingData' => [
                                'sms'   => false,
                                'email' => false
                            ]
                        ],
                        'connections'   => []
                    ];
                }

                $profileStructure['locations']['list'][$connection['serial']]['connections'][] = [
                    'totalDownload' => $connection['totalDownload'],
                    'totalUpload'   => $connection['totalUpload'],
                    'uptime'        => $connection['uptime'],
                    'connectedAt'   => $connection['connectedAt'],
                    'lastseenAt'    => $connection['lastseenAt']
                ];
                $profileStructure['locations']['list'][$connection['serial']]['totalDownload'] += $connection['totalDownload'];
                $profileStructure['locations']['list'][$connection['serial']]['totalUpload']   += $connection['totalUpload'];
                $profileStructure['locations']['list'][$connection['serial']]['uptime']        += $connection['uptime'];
                $profileStructure['locations']['list'][$connection['serial']]['logins']        += $connection['logins'];

                $profileStructure['locations']['totals']['download'] += $connection['totalDownload'];
                $profileStructure['locations']['totals']['upload']   += $connection['totalUpload'];
                $profileStructure['locations']['totals']['uptime']   += $connection['uptime'];
                $profileStructure['locations']['totals']['logins']   += $connection['logins'];
            }
        }

        $payments = $this->userPayments($id, $serials);

        if (!empty($payments)) {
            foreach ($payments as $key => $payment) {
                $profileStructure['locations']['totals']['payments']['sum'] += $payment['paymentAmount'];

                $profileStructure['locations']['list'][$payment['serial']]['payments']['sum']              += $payment['paymentAmount'] / 100;
                $profileStructure['locations']['list'][$payment['serial']]['payments']['count']            += 1;
                $profileStructure['locations']['list'][$payment['serial']]['payments']['actualPayments'][] = $payment;
            }

            $profileStructure['locations']['totals']['payments']['sum']   = $profileStructure['locations']['totals']['payments']['sum'] / 100;
            $profileStructure['locations']['totals']['payments']['count'] = sizeof($payments);
        }

        $optedOutMarketing = $this->em->createQueryBuilder()
            ->select('u.serial, u.type')
            ->from(MarketingOptOut::class, 'u')
            ->where('u.optOut = :o');
        if (!empty($serials)) {
            $optedOutMarketing = $optedOutMarketing->andWhere('u.serial IN (:serials)')
                ->setParameter('serials', $serials);
        }
        $optedOutMarketing = $optedOutMarketing->andWhere('u.uid = :i')
            ->setParameter('i', $id)
            ->setParameter('o', true)
            ->getQuery()
            ->getArrayResult();

        foreach ($optedOutMarketing as $key => $value) {
            $profileStructure['locations']['list'][$value['serial']]['optedOut']['marketingData'][$value['type']] = true;
        }

        $optedOutLocation = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(LocationOptOut::class, 'u')
            ->where('u.profileId = :i');
        if (!empty($serials)) {
            $optedOutLocation = $optedOutLocation->where('u.serial IN (:s)')
                ->setParameter('s', $serials);
        }
        $optedOutLocation = $optedOutLocation->andWhere('u.deleted = :false')
            ->setParameter('i', $id)
            ->setParameter('false', false)
            ->getQuery()
            ->getArrayResult();

        foreach ($optedOutLocation as $key => $value) {
            $profileStructure['locations']['list'][$value['serial']]['optedOut']['locationData']           = true;
            $profileStructure['locations']['list'][$value['serial']]['optedOut']['marketingData']['sms']   = true;
            $profileStructure['locations']['list'][$value['serial']]['optedOut']['marketingData']['email'] = true;
        }

        $marketing = $this->userMarketingData($id, $serials);

        if (!empty($marketing)) {
            foreach ($marketing as $key => $value) {
                if (!isset($profileStructure['marketing']['locations'][$value['serial']])) {
                    $profileStructure['marketing']['locations'][$value['serial']] = [
                        'email'    => 0,
                        'sms'      => 0,
                        'optedOut' => false,
                        'name'     => $value['alias'],
                        'events'   => []
                    ];
                }

                $profileStructure['marketing']['locations'][$value['serial']]['events'][] = [
                    'timestamp' => $value['timestamp'],
                    'type'      => $value['type']
                ];

                $profileStructure['marketing']['locations'][$value['serial']][$value['type']] += 1;
                $profileStructure['marketing']['totals'][$value['type']]                      += 1;
            }
        }

        $devices = $this->userDevices($id, $serials);

        if (!empty($devices)) {
            foreach ($devices as $key => $device) {
                $profileStructure['devices'][] = $device;
            }
        }

        return Http::status(200, $profileStructure);
    }


    private function userProfile(string $id)
    {
        $baseProfile = $this->em->createQueryBuilder()
            ->select(
                'u.email,
                        u.first,
                        u.last,
                        u.email,
                        u.gender,
                        u.phone,
                        u.postcode,
                        u.postcodeValid,
                        u.age,
                        u.birthMonth,
                        u.birthDay,
                        u.verified'
            )
            ->from(UserProfile::class, 'u')
            ->where('u.id = :i')
            ->setParameter('i', $id)
            ->getQuery()
            ->getArrayResult()[0];

        $baseProfile['avatar'] = [
            'type'     => 'name',
            'resource' => ''
        ];

        $firstName = isset($baseProfile['first']) && !empty($baseProfile['first']) ? $baseProfile['first'] : '';

        $lastName = isset($baseProfile['last']) && !empty($baseProfile['last']) ? $baseProfile['last'] : '';

        if (!empty($firstName)) {
            $baseProfile['avatar']['resource'] = $firstName;
        }

        if (!empty($lastName)) {
            $baseProfile['avatar']['resource'] .= ' ' . $lastName;
        }

        if (!empty($baseProfile['avatar']['resource'])) {
            $baseProfile['avatar']['resource'] = trim($baseProfile['avatar']['resource']);
        }

        $hasSignedInViaGoogle = $this->em->createQueryBuilder()
            ->select('p.userId')
            ->from(Provider::class, 'p')
            ->where('p.uid = :uid')
            ->andWhere('p.type = :google')
            ->setParameter('uid', $id)
            ->setParameter('google', 'google')
            ->getQuery()
            ->getArrayResult();

        if (!empty($hasSignedInViaGoogle)) {
            $baseProfile['avatar']['type']     = 'googleId';
            $baseProfile['avatar']['resource'] = $hasSignedInViaGoogle[0]['userId'];
        }

        return $baseProfile;
    }

    private function userPayments(string $id, array $serials)
    {
        $sql = $this->em->createQueryBuilder()
            ->select('
            upa.email,
            upa.serial,
            upa.creationdate,
            upa.transactionId,
            upa.duration,
            upa.paymentAmount,
            upa.devices,
            upa.planId,
            upa.status')
            ->from(UserPayments::class, 'upa');
        if (!empty($serials)) {
            $sql = $sql->join(UserData::class, 'ud', 'WITH', 'upa.serial = ud.serial');
        }

        $sql = $sql->where('upa.profileId = :id');
        if (!empty($serials)) {
            $sql = $sql->andWhere('ud.serial IN (:serials)')
                ->setParameter('serials', $serials);
        }
        $sql = $sql->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        return $sql;
    }

    private function userConnections(string $id, array $serials)
    {
        $sql = $this->em->createQueryBuilder()
            ->select('
            COALESCE(SUM(ud.dataUp),0) as totalUpload, 
            COALESCE(SUM(ud.dataDown),0) as totalDownload,
            SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime,
            MAX(ud.timestamp) as connectedAt,
            MAX(ud.lastupdate) as lastseenAt,
            ud.lastupdate as lastupdate,
            ud.serial,
            ns.alias,
            COUNT(ud.id) as logins,
            lb.headerImage as logo')
            ->from(UserData::class, 'ud')
            ->leftJoin(LocationSettings::class, 'ns', 'WITH', 'ud.serial = ns.serial')
            ->leftJoin(LocationBranding::class, 'lb', 'WITH', 'ns.branding = lb.id')
            ->where('ud.profileId = :id');
        if (!empty($serials)) {
            $sql = $sql->andWhere('ud.serial IN (:serials)')
                ->setParameter('serials', $serials);
        }
        $sql = $sql->setParameter('id', $id)
            ->groupBy('ud.id')
            ->getQuery()
            ->getArrayResult();

        return $sql;
    }

    private function userMarketingData(string $id, array $serials)
    {
        $sql = $this->em->createQueryBuilder()
            ->select('
            me.type, 
            me.serial,
            me.timestamp, 
            ns.alias')
            ->from(MarketingEvents::class, 'me')
            ->leftJoin(LocationSettings::class, 'ns', 'WITH', 'me.serial = ns.serial')
            ->where('me.profileId = :id');
        if (!empty($serials)) {
            $sql = $sql->andWhere('me.serial IN (:serials)')
                ->setParameter('serials', $serials);
        }
        $sql = $sql->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        return $sql;
    }

    private function userDevices(string $id, array $serials)
    {
        $sql = $this->em->createQueryBuilder()
            ->select('
            SUM(ud.dataDown) as dataDown,
            SUM(ud.dataUp) as dataUp,
            ud.mac,
            device.brand,
            device.model,
            browser.name,
            browser.version,
            ud.serial')
            ->from(UserData::class, 'ud')
            ->leftJoin(UserDevice::class, 'device', 'WITH', 'device.mac = ud.mac')
            ->leftJoin(UserAgent::class, 'agent', 'WITH', 'agent.userDeviceId = device.id')
            ->leftJoin(DeviceBrowser::class, 'browser', 'WITH', 'browser.id = agent.deviceBrowserId')
            ->where('ud.profileId = :id');
        if (!empty($serials)) {
            $sql = $sql->andWhere('ud.serial IN (:serials)')
                ->setParameter('serials', $serials);
        }
        $sql = $sql->setParameter('id', $id)
            ->groupBy('ud.mac')
            ->getQuery()
            ->getArrayResult();

        return $sql;
    }
}
