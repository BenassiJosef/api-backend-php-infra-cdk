<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/05/2017
 * Time: 10:50
 */

namespace App\Controllers\Locations\Reports;

use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Models\Locations\LocationSettings;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Models\UserRegistration;
use Doctrine\ORM\Tools\Pagination\Paginator;

class _RegistrationReportController extends ReportController implements IReport
{
    private $defaultOrder = 'ur.createdAt';
    private $exportHeaders = [];

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        if ($options['order'] === 'up.timestamp') {
            $options['order'] = 'join_date';
        }

        $maxResults    = 50;
        $registrations = $this->em->createQueryBuilder()
            ->select('up.id, 
            up.first, 
            up.last, 
            up.email, 
            up.gender, 
            COUNT(up.email) as logins,
            COUNT(DISTINCT(up.id)) as registrations,
            ur.createdAt as join_date,
            up.phone,
            UPPER(up.postcode) AS postcode,
            up.ageRange,
            up.birthMonth,
            up.birthDay,
            up.opt,
            up.lat, 
            up.lng, 
            up.custom, 
            up.country, 
            up.verified')
            ->from(UserRegistration::class, 'ur')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'ur.profileId = up.id')
            ->where('ur.serial IN (:serial)')
            ->andWhere('ur.createdAt BETWEEN :now AND :then')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('then', $end)
            ->groupBy('ur.profileId')
            ->orderBy($this->defaultOrder, $options['sort'])
            ->setFirstResult($options['offset'])
            ->setMaxResults($maxResults);

        $results = new Paginator($registrations);
        $results->setUseOutputWalkers(false);

        $registrations = $results->getIterator()->getArrayCopy();

        if (!empty($registrations)) {

            $keysToHide = [
                'first',
                'last',
                'phone',
                'email',
                'opt',
                'ageRange',
                'postcode',
                'birthMonth',
                'birthDay',
                'country',
                'verified',
                'lat',
                'lng',
                'custom'
            ];

            $customQuestions = $this->fetchHeadersForSerial($serial[0]);

            foreach ($customQuestions as $key => $customQuestion) {
                if (!isset($customQuestion['id'])) {
                    continue;
                }

                foreach ($registrations as $k => $registration) {
                    if (!isset($registration['custom'][$serial[0]])) {
                        continue;
                    }
                    foreach ($registration['custom'][$serial[0]] as $qK => $item) {
                        if ($qK === $customQuestion['id']) {
                            $registrations[$k][$customQuestion['id']] = $item;
                        }
                    }
                }
            }


            foreach ($registrations as $key => $registration) {
                if (is_null($registration['email']) || empty($registration['email']) || strpos($registration['email'],
                        '@') === false) {
                    foreach ($registration as $k => $v) {
                        if (in_array($k, $keysToHide)) {
                            $registrations[$key][$k] = 'OPT_OUT';
                        }
                    }
                } else {
                    foreach ($registration as $k => $v) {
                        if (is_numeric($v) && $k !== 'phone') {
                            $registrations[$key][$k] = (int)round($v);
                        }
                    }
                    unset($registrations[$key]['custom']);
                }
            }

            $return = [
                'table'       => $registrations,
                'has_more'    => false,
                'total'       => count($results),
                'next_offset' => $options['offset'] + $maxResults
            ];

            if ($options['offset'] <= $return['total'] && count($registrations) !== $return['total']) {
                $return['has_more'] = true;
            }

            return $return;
        }

        return [];
    }

    public function fetchHeadersForSerial(string $serial)
    {

        $location = $this->em->createQueryBuilder()
                        ->select('u.type, u.other, u.freeQuestions, u.customQuestions')
                        ->from(LocationSettings::class, 'u')
                        ->where('u.serial = :serial')
                        ->setParameter('serial', $serial)
                        ->getQuery()
                        ->getArrayResult()[0];

        if (empty($location)) {
            return false;
        }

        $questions = [
            [
                'question' => 'Join Date',
                'key'      => 'join_date'
            ]
        ];


        if ($location['type'] === 1) {
            $questions[] = [
                'question' => 'Email',
                'key'      => 'email'
            ];

            $question[] = [
                'question' => 'Firstname',
                'key'      => 'first'
            ];

            $questions[] = [
                'question' => 'Lastname',
                'key'      => 'last'
            ];

            $questions[] = [
                'question' => 'Phone',
                'key'      => 'phone'
            ];

            return $questions;
        }


        foreach ($location['freeQuestions'] as $question) {
            if ($question === 'Email') {
                $questions[] = [
                    'question' => $question,
                    'key'      => 'email'
                ];
            }
            if ($question === 'Firstname') {
                $questions[] = [
                    'question' => $question,
                    'key'      => 'first'
                ];
            }
            if ($question === 'Lastname') {
                $questions[] = [
                    'question' => $question,
                    'key'      => 'last'
                ];
            }
            if ($question === 'Postcode') {
                $questions[] = [
                    'question' => $question,
                    'key'      => 'postcode'
                ];
            }
            if ($question === 'Optin') {
                $questions[] = [
                    'question' => $question,
                    'key'      => 'opt'
                ];
            }
            if ($question === 'Phone') {
                $questions[] = [
                    'question' => $question,
                    'key'      => 'phone'
                ];
            }
            if ($question === 'DoB') {
                $questions[] = [
                    'question' => 'Birth Month',
                    'key'      => 'birthMonth'
                ];

                $questions[] = [
                    'question' => 'Birth Day',
                    'key'      => 'birthDay'
                ];
            }
            if ($question === 'Gender') {
                $questions[] = [
                    'question' => $question,
                    'key'      => 'gender'
                ];
            }
            if ($question === 'Country') {
                $questions[] = [
                    'question' => $question,
                    'key'      => 'country'
                ];
            }
        }

        $customQuestions = $location['customQuestions'];

        if (!is_null($customQuestions)) {
            foreach ($customQuestions as $custom) {
                $questions[] = [
                    'question' => $custom['name'],
                    'id'       => $custom['id'],
                    'key'      => $custom['id'],
                    'type'     => 'custom'
                ];
            }
        }

        $otherController = new LocationOtherController($this->em);
        $other           = $otherController->getNearlyOther($serial, $location['other'])['message'];

        if (array_key_exists('validation', $other)) {
            if ($other['validation'] === 1) {
                $questions[] = [
                    'question' => 'Validation',
                    'key'      => 'verified'
                ];
            }
        }

        return $questions;
    }


    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {

        if (!isset($options['order'])) {
            $this->defaultOrder = 'timestamp';
        }

        $sql = '
        UNIX_TIMESTAMP(ur.createdAt) as timestamp, 
        COUNT(ud.id) as logins,
        COUNT(DISTINCT(ur.profileId)) as registrations, 
        SUM(up.verified) as verified,
        COALESCE(SUM(ud.authTime),0) as timeTaken';

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', YEAR(ur.createdAt) as year, MONTH(ur.createdAt) as month, DAY(ur.createdAt) as day, HOUR(ur.createdAt) as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', YEAR(ur.createdAt) as year, MONTH(ur.createdAt) as month, DAY(ur.createdAt) as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(ur.createdAt) as year, WEEK(ur.createdAt) as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', YEAR(ur.createdAt) as year, MONTH(ur.createdAt) as month';
            $group = 'year, month';
        }

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserRegistration::class, 'ur')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'ur.profileId = up.id')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId')
            ->where('ur.serial IN (:serial)')
            ->andWhere('ur.createdAt BETWEEN :start AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy($group)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->getQuery()
            ->getArrayResult();

        if (!empty($totals)) {
            foreach ($totals as $key => $registration) {
                foreach ($registration as $k => $v) {
                    if (is_numeric($v)) {
                        $totals[$key][$k] = (int)round($v);
                    }
                }

                if (!empty($registration['custom'])) {
                    if (array_key_exists($serial[0], $registration['custom'])) {
                        $registrations[$key]['custom'] = $registration['custom'][$serial[0]];
                    }
                }
            }

            return $totals;
        }

        return [];
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        if (!isset($options['order'])) {
            $this->defaultOrder = 'up.timestamp';
        }


        $verified = $this->em->createQueryBuilder()
            ->select('SUM(up.verified)')
            ->from(UserRegistration::class, 'ur')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'up.id = ur.profileId')
            ->where('ur.serial IN (:serial)')
            ->andWhere('ur.createdAt BETWEEN :start AND :end')
            ->andWhere('up.verified = 1')
            ->setParameter('serial', $serial)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('ur.profileId')
            ->getQuery()
            ->getArrayResult();

        $sql = ' 
        COUNT(up.id) as logins,
        COUNT(DISTINCT(ur.profileId)) as registrations,
        COALESCE(SUM(ud.authTime),0) as timeTaken,
        COALESCE(AVG(ud.authTime),0) as avgTimeTaken';

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserRegistration::class, 'ur')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'up.id = ur.profileId')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId')
            ->where('ur.serial IN (:serial)')
            ->andWhere('ur.createdAt BETWEEN :start AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('ur.createdAt', $options['sort'])
            ->getQuery()
            ->getArrayResult();

        if (!empty($totals)) {
            foreach ($totals as $key => $registration) {
                foreach ($registration as $k => $v) {
                    if (is_numeric($v)) {
                        $totals[$key][$k] = (int)round($v);
                    }
                }
            }

            $totals[0]['verified'] = sizeof($verified);

            return $totals[0];
        }

        return [];
    }

    public function export(array $serial, \DateTime $start, \DateTime $end, array $options)
    {
        if ($options['order'] === 'up.timestamp') {
            $options['order'] = 'join_date';
        }

        $registrations = $this->em->createQueryBuilder()
            ->select('up.id, 
            up.first, 
            up.last, 
            up.email, 
            up.gender, 
            COUNT(up.email) as logins,
            COUNT(DISTINCT(up.id)) as registrations,
            up.timestamp as join_date,
            up.phone,
            UPPER(up.postcode) AS postcode,
            up.ageRange,
            up.birthMonth,
            up.birthDay,
            up.opt,
            up.lat, 
            up.lng, 
            up.custom, 
            up.country, 
            ud.type, 
            up.verified')
            ->from(UserRegistration::class, 'ur')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'ur.profileId = up.id')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId')
            ->where('ur.serial IN (:serial)')
            ->andWhere('ur.createdAt BETWEEN :now AND :then')
            ->andWhere('ud.dataUp IS NOT NULL')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('then', $end)
            ->groupBy('ur.profileId')
            ->orderBy($this->defaultOrder, $options['sort']);

        $results = new Paginator($registrations);
        $results->setUseOutputWalkers(false);

        $registrations = $results->getIterator()->getArrayCopy();

        if (!empty($registrations)) {

            $keysToHide = [
                'first',
                'last',
                'phone',
                'email',
                'opt',
                'ageRange',
                'postcode',
                'birthMonth',
                'birthDay',
                'country',
                'verified',
                'lat',
                'lng',
                'custom'
            ];

            $customQuestions = $this->fetchHeadersForSerial($serial[0]);

            foreach ($customQuestions as $key => $customQuestion) {
                if (!isset($customQuestion['id'])) {
                    continue;
                }

                foreach ($registrations as $k => $registration) {
                    if (!isset($registration['custom'][$serial[0]])) {
                        continue;
                    }
                    foreach ($registration['custom'][$serial[0]] as $qK => $item) {
                        if ($qK === $customQuestion['id']) {
                            $registrations[$k][$customQuestion['id']] = $item;
                        }
                    }
                }
            }


            foreach ($registrations as $key => $registration) {
                if (is_null($registration['email']) || empty($registration['email']) || strpos($registration['email'],
                        '@') === false) {
                    foreach ($registration as $k => $v) {
                        if (in_array($k, $keysToHide)) {
                            $registrations[$key][$k] = 'OPT_OUT';
                        }
                    }
                } else {
                    foreach ($registration as $k => $v) {
                        if (is_numeric($v) && $k !== 'phone') {
                            $registrations[$key][$k] = (int)round($v);
                        }
                    }
                    unset($registrations[$key]['custom']);
                }
            }

            $registrationsForCSV = [];

            foreach ($customQuestions as $question) {
                array_push($this->exportHeaders, $question['question']);
                foreach ($registrations as $k => $registration) {
                    if (isset($question['key'])) {
                        if (isset($registration[$question['key']])) {
                            $registrationsForCSV[$k][] = $registration[$question['key']];
                        }
                    } elseif (isset($question['id'])) {
                        if (isset($registration[$question['question']])) {
                            $registrationsForCSV[$k][] = $registration[$question['question']];
                        }
                    }
                }
            }

            return $registrationsForCSV;
        }

        return [];
    }

    public function getExportHeaders()
    {
        return $this->exportHeaders;
    }

    public function getDefaultOrder(): string
    {
        return $this->defaultOrder;
    }

    public function setDefaultOrder(array $options)
    {
        if (array_key_exists('order', $options) && !is_null($options['order'])) {
            $this->defaultOrder = $options['order'];
        }
    }
}
