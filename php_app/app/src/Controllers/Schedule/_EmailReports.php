<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 25/05/2017
 * Time: 11:26
 */

namespace App\Controllers\Schedule;

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Reports\_LocationReportController;
use App\Controllers\Notifications\FirebaseCloudMessagingController;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Reports\EmailReport;
use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use App\Models\Notifications\Notification;
use App\Models\Notifications\NotificationType;
use App\Models\OauthUser;
use App\Package\Organisations\UserRoleChecker;
use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Slim\Http\Response;
use Slim\Http\Request;

class _EmailReports
{
    protected $em;
    protected $mail;
    protected $mp;

    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->mail = new _MailController($this->em);
    }

    public function runRoute(Request $request, Response $response)
    {

        $queryParams = $request->getQueryParams();
        $offset      = 0;
        if (isset($queryParams['offset'])) {
            $offset = $queryParams['offset'];
        }

        $send = $this->run($queryParams['kind'], $offset);

        $this->em->clear();

        return $response->withJson($send, 200);
    }


    /**
     * @param string $kind
     * @param $offset
     * @return array
     * @throws Exception
     */
    private function run(string $kind, $offset)
    {
        $this->mp = new _Mixpanel();
        /**
         * Get a maximum of 10 Members that have signed up for this kind of email Report
         */

        $getMembers = $this->em->createQueryBuilder()
                               ->select('e.uid, e.additionalInfo')
                               ->from(NotificationType::class, 'e')
                               ->where('e.type = :em')
                               ->andWhere('e.notificationKind = :nk')
                               ->setParameter('em', 'email')
                               ->setParameter('nk', $kind)
                               ->setFirstResult($offset)
                               ->setMaxResults(10);

        $results = new Paginator($getMembers);
        $results->setUseOutputWalkers(false);

        $getMembers = $results->getIterator()->getArrayCopy();

        if (empty($getMembers)) {
            return Http::status(200);
        }

        $uIds = [];

        /**
         * Create an array of UIDs from the Member objects retrieved
         */

        foreach ($getMembers as $key => $member) {
            if (in_array($member['uid'], $uIds)) {
                continue;
            }
            $uIds[] = $member['uid'];
        }

        $urc      = new UserRoleChecker($this->em);
        $getSites = [];
        foreach ($uIds as $id) {
            $locations = $urc->locationForUserId($id);
            foreach ($locations as $location) {
                $getSites[] = ['serial' => $location->getSerial(), 'alias' => $location->getAlias(), 'memberId' => $id];

            }
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 10
        ];

        if ($offset <= $return['total'] && count($getMembers) !== $return['total']) {
            $return['has_more'] = true;
        }


        /**
         * Create an array of objects that for each member.
         * Each object is the serial and alias of a site to generate a report for.
         */

        foreach ($getMembers as $key => $member) {
            foreach ($getSites as $ke => $site) {
                if ($site['memberId'] !== $member['uid']) {
                    continue;
                }

                $siteStructure = [
                    'serial' => '',
                    'alias'  => ''
                ];

                if (!is_null($site['alias'])) {
                    $siteStructure['alias'] = $site['alias'];
                }

                $siteStructure['serial'] = $site['serial'];

                $getMembers[$key]['sites'][] = $siteStructure;
            }
        }

        foreach ($getMembers as $key => $value) {
            $toDetails                = $this->getNameFromUid($getMembers[$key]['uid']);
            $getMembers[$key]['name'] = $toDetails['name'];

            $getMembers[$key]['notificationKind'] = $kind;

            if ($kind === 'insight_daily') {
                $getMembers[$key]['kind'] = 'day';
            } elseif ($kind === 'insight_weekly') {
                $getMembers[$key]['kind'] = 'week';
            } elseif ($kind === 'insight_biWeekly') {
                $getMembers[$key]['kind'] = 'weeks';
            } elseif ($kind === 'insight_monthly') {
                $getMembers[$key]['kind'] = 'month';
            } elseif ($kind === 'insight_biMonthly') {
                $getMembers[$key]['kind'] = 'months';
            }
        }

        $newDateTimeEnd = new \DateTime();


        /**
         * Example Structure
         * [
         * 0 =>
         * [
         * 'uid' => '02Ibmr0kNSXDMG6FjVsFpE4YkDX2',
         * 'additionalInfo' => 'jamie.aitken@blackbx.io',
         * 'sites' =>
         * [
         * 0 =>
         * [
         * 'serial' => 'TXQM189D6BRJ',
         * 'alias' => 'Windmill Taverns - Kings Arms',
         * ],
         * 1 =>
         * [
         * 'serial' => 'QZU5MZA3CIZU',
         * 'alias' => 'Windmill Taverns - Mc and Sons',
         * ],
         * 2 =>
         * [
         * 'serial' => 'XUMNY8G4SQP1',
         * 'alias' => 'Windmill Taverns - The Ring Bar',
         * ],
         * ],
         * 'name' => 'Victoria Mawson',
         * 'kind' => 'month',
         * ]
         * ]
         */

        $fcmController = new FirebaseCloudMessagingController($this->em);

        foreach ($getMembers as $member => $memberValues) {

            /**
             * If the member doesnt have a sites object, continue onto the next member
             */

            if (!isset($memberValues['sites'])) {
                continue;
            }

            foreach ($memberValues['sites'] as $key => $site) {
                $newDateTimeToUseForStart = new \DateTime();
                $number                   = 1;
                if (substr($memberValues['kind'], strlen($memberValues['kind']) - 1) === 's') {
                    $number = 2;
                } else {
                    $memberValues['kind'] = $memberValues['kind'] . 's';
                }
                $dataToSend = $this->buildReport(
                    $site['serial'],
                    $newDateTimeToUseForStart->modify('-' . $number . ' ' . $memberValues['kind'])->getTimestamp(),
                    $newDateTimeEnd->getTimestamp(), $memberValues['uid']
                );
                if ($dataToSend === 'NO_SUITABLE_DATA_FOR_THIS_SERIAL_WITHIN_THIS_TIME_FRAME_COULD_BE_LOCATED') {
                    $this->mp->track(
                        'EMAIL_REPORTS: DID_NOT_MANAGE_TO_GENERATE_DATA', [
                        'site'      => [
                            'serial' => $site['serial'],
                            'alias'  => $site['alias']
                        ],
                        'dataRange' => [
                            'startDate' => $newDateTimeToUseForStart->modify('-' . $number . ' ' . $memberValues['kind'])->getTimestamp(),
                            'endDate'   => $newDateTimeEnd->getTimestamp()
                        ]
                    ]
                    );
                    continue;
                }


                $upperCaseKind = ucfirst($memberValues['kind']);

                $notification = new Notification(
                    'report', $upperCaseKind . ' Report for ' . $site['alias'],
                    $memberValues['notificationKind'],
                    ''
                );

                $notification->setMessage(
                    "You've had " . $dataToSend['registrations']['registrations'] . " new customers, " . $dataToSend['customers']['customers'] . " total customers which means " . $dataToSend['customers']['return'] . " were returning"
                );

                $fcmController->sendMessage(
                    $memberValues['uid'], $notification

                );

                try {
                    $this->mail->send(
                        [
                            [
                                'name' => $memberValues['name'],
                                'to'   => $memberValues['additionalInfo']
                            ]
                        ],
                        array_merge(
                            $dataToSend, [
                            'startTime'  => $newDateTimeToUseForStart->format('F j'),
                            'endTime'    => $newDateTimeEnd->format('F j, Y'),
                            'reportKind' => $upperCaseKind,
                            'serial'     => $site['serial'],
                            'alias'      => $site['alias']
                        ]
                        ),
                        'EmailReport',
                        $upperCaseKind . ' Report for ' . $site['alias']
                    );
                } catch (Exception $exception) {
                    $this->mp->track(
                        'EMAIL_REPORTS: FAILED_TO_SEND_' . $upperCaseKind, [
                        'member' => [
                            'name' => $memberValues['name'],
                            'to'   => $memberValues['additionalInfo']
                        ],
                        'site'   => [
                            'serial' => $site['serial'],
                            'alias'  => $site['alias']
                        ],
                        'error'  => $exception->getMessage()
                    ]
                    );
                }
            }
        }

        return Http::status(200, $return);
    }

    private function buildReport($serial, $timeBegin, $timeEnd, string $uid)
    {
        $locationReport = new _LocationReportController($this->em);

        return $locationReport->determineChartKind(
            'overview',
            [$serial],
            $timeBegin,
            $timeEnd,
            ['export' => false, 'table' => false, 'totals' => true, 'line' => false],
            ['uid' => $uid]
        )['message'];
    }

    private function getNameFromUid(string $uid)
    {
        $user = $this->em->createQueryBuilder()
                         ->select('u.first, u.last')
                         ->from(OauthUser::class, 'u')
                         ->where('u.uid = :uid')
                         ->setParameter('uid', $uid)
                         ->getQuery()
                         ->getArrayResult();

        if (empty($user)) {
            return null;
        }

        return ['name' => $user[0]['first'] . ' ' . $user[0]['last']];
    }

    private function getReportUsers(array $user)
    {
        $results = $this->em->createQueryBuilder()
                            ->select('u')
                            ->from(EmailReport::class, 'u')
                            ->where('u.uid = :id') // TODO OrgId replace
                            ->setParameter('id', $user['uid'])
                            ->getQuery()
                            ->getArrayResult();
        if (empty($results)) {
            return Http::status(204);
        }

        return Http::status(200, $results);
    }

    private function addReportUser(array $user, array $body, string $serial)
    {

        $validate = Validation::pastRouteBodyCheck($body, ['daily', 'weekly', 'biWeekly', 'monthly', 'biMonthly']);

        if (is_array($validate)) {
            return Http::status(400, 'REQUIRES' . '_' . strtoupper(implode('_', $validate)));
        }

        $newDateTime = new \DateTime();

        $createdAt = $newDateTime->getTimestamp();

        $userAlreadyReceivingEmailsForSerial = $this->em->getRepository(EmailReport::class)->findOneBy(
            [
                'serial' => $serial,
                'uid'    => $user['uid']
            ]
        );

        if (is_object($userAlreadyReceivingEmailsForSerial)) {
            $userAlreadyReceivingEmailsForSerial->daily     = $body['daily'];
            $userAlreadyReceivingEmailsForSerial->weekly    = $body['weekly'];
            $userAlreadyReceivingEmailsForSerial->biWeekly  = $body['biWeekly'];
            $userAlreadyReceivingEmailsForSerial->monthly   = $body['monthly'];
            $userAlreadyReceivingEmailsForSerial->biMonthly = $body['biMonthly'];
        } else {
            $newReportUser = new EmailReport(
                $user['uid'],
                $serial,
                $body['daily'],
                $body['weekly'],
                $body['biWeekly'],
                $body['monthly'],
                $body['biMonthly'],
                $createdAt
            );
            $this->em->persist($newReportUser);
        }
        $this->em->flush();

        return Http::status(200);
    }
}
