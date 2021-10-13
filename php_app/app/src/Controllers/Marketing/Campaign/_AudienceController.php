<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 08/06/2017
 * Time: 10:51
 */

namespace App\Controllers\Marketing\Campaign;

use App\Models\UserData;
use App\Models\UserProfile;
use Doctrine\ORM\EntityManager;

class _AudienceController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function audienceCalculator(array $rules = [], array $profile)
    {
        $today        = new \DateTime();
        $currentMonth = date('m');
        $currentDay   = date('d');
        $currentYear  = date('Y');
        $operators    = [
            '='  => function ($a, $b) {
                return $a === $b;
            },
            '!=' => function ($a, $b) {
                return $a != $b;
            },
            '>=' => function ($a, $b) {
                return $a >= $b;
            },
            '>'  => function ($a, $b) {
                return $a > $b;
            },
            '<=' => function ($a, $b) {
                return $a <= $b;
            },
            '<'  => function ($a, $b) {
                return $a < $b;
            }
        ];
        $condition    = [
            'AND' => function ($a, $b) {
                return $a && $b;
            },
            'OR'  => function ($a, $b) {
                return $a || $b;
            }
        ];

        $timeCalc = [
            'today'    => function ($birthdate, $birthmonth, $birthday) {
                $currentMonth = (int)date('n');
                $currentDay   = (int)date('j');

                return (($currentDay === $birthday) && ($currentMonth === $birthmonth));
            },
            'tomorrow' => function ($birthdate, $birthmonth, $birthday) {
                $today = new \DateTime();
                $today->modify('+1 day');
                $today->setTime(0, 0, 0);

                return ($today == $birthdate);
            },
            'week'     => function ($birthdate, $birthmonth, $birthday) {
                $today = new \DateTime();
                $today->modify('+1 week');
                $today->setTime(0, 0, 0);

                return ($today == $birthdate);
            },
            'month'    => function ($birthdate, $birthmonth, $birthday) {
                $today = new \DateTime();
                $today->modify('+1 month');
                $today->setTime(0, 0, 0);

                return ($today == $birthdate);
            }
        ];

        $timeCalcForNotSeen = [
            'day'    => function ($lastUpdate) {
                $today = new \DateTime();
                $today->modify('-1 day');

                return ($today >= $lastUpdate);
            },
            'week'   => function ($lastUpdate) {
                $week = new \DateTime();
                $week->modify('-1 week');

                return ($week >= $lastUpdate);
            },
            '2week'  => function ($lastUpdate) {
                $twoWeek = new \DateTime();
                $twoWeek->modify('-2 week');

                return ($twoWeek >= $lastUpdate);
            },
            'month'  => function ($lastUpdate) {
                $month = new \DateTime();
                $month->modify('-1 month');

                return ($month >= $lastUpdate);
            },
            '2month' => function ($lastUpdate) {
                $twoMonth = new \DateTime();
                $twoMonth->modify('-2 month');

                return ($twoMonth >= $lastUpdate);
            }
        ];

        $sends = [];
        $send  = false;

        foreach ($rules as $rule) {
            if (!isset($rule['value'])) {
                continue;
            }

            $send  = false;
            $event = $rule['event'];
            $value = $rule['value'];
            if (is_numeric($value)) {
                $value = (int)$value;
            }
            if ($event === 'connections') {
                $send = call_user_func($operators[$rule['operand']], (int)$profile['connections'], $value);
            }
            if ($event === 'gender') {
                $gender = $profile['gender'];
                if (!empty($gender)) {
                    $send = call_user_func($operators[$rule['operand']], $gender, $value);
                }
            }
            if ($event === 'left') {
                if ($profile['lastupdate'] !== 0) {
                    $sendAt = new \DateTime();
                    $sendAt->setTimestamp($profile['lastupdate']);
                    $sendAt->modify('+' . $value . ' minutes');
                    $send = $today >= $sendAt;
                }
            }
            if ($event === 'timespent') {
                if ($profile['lastupdate'] !== 0) {
                    $lastSeen = new \DateTime();
                    $joined   = new \DateTime();
                    $lastSeen->setTimestamp((int)$profile['lastupdate']);
                    $joined->setTimestamp((int)$profile['joined']);
                    $timeToSend = $joined->modify('+' . $value . ' minutes');
                    $send       = ($timeToSend <= $lastSeen && $today >= $timeToSend);
                }
            }
            if ($event === 'birthday') {
                $day   = (int)$profile['birthDay'];
                $month = (int)$profile['birthMonth'];
                if ($day && $month) {
                    $birthday = new \DateTime();
                    $birthday->setTime(0, 0, 0);
                    $birthday->setDate($currentYear, $month, $day);
                    $send = call_user_func($timeCalc[$value], $birthday, $month, $day);
                }
            }
            if ($event === 'notSeen') {
                $newDateTime = new \DateTime();
                $send        = call_user_func($timeCalcForNotSeen[$value],
                    $newDateTime->setTimestamp($profile['lastupdate']));
            }
            $sends[] = $send;
        };

        $res = false;
        if (count($rules) >= 2) {
            $k1         = 0;
            $k2         = 1;
            $conditions = [];
            foreach ($rules as $key => $rule) {
                if ($k2 === count($rules)) {
                    continue;
                }
                if (!isset($rule['condition'])) {
                    continue;
                }
                if ($rule['condition']) {
                    $isTrue       = call_user_func($condition[$rule['condition']], $sends[$k1], $sends[$k2]);
                    $conditions[] = $isTrue;
                }
                $k1++;
                $k2++;
            }

            $res = true;
            foreach ($conditions as $c) {
                if ($c === false) {
                    $res = false;
                }
            }
        } else {
            $res = $send;
        }

        return $res;
    }

    public function getAudienceWithInOneMonth($serials)
    {
        $now      = new \DateTime();
        $audience = $this->em->createQueryBuilder()
            ->select('
            up.id,
            up.phone,
            up.phoneValid,
            up.email,
            up.verified,
            up.first,
            up.last,
            up.country,
            up.gender,
            up.birthDay,
            up.birthMonth,
            ud.serial,
            MAX(ud.timestamp) as joined,
            UNIX_TIMESTAMP(MAX(ud.lastupdate)) as lastupdate,
            MAX(ud.lastupdate) as humanLast,
            COALESCE(SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)), 0) as timespent,
            COUNT(ud.profileId) as connections')
            ->from(UserData::class, 'ud')
            ->innerJoin(UserProfile::class, 'up', 'WITH', 'ud.profileId = up.id')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.timestamp > :time')
            ->andWhere('up.email IS NOT NULL')
            ->setParameter('time', $now->modify('-1 month'))
            ->setParameter('serial', $serials)
            ->groupBy('up.id')
            ->getQuery()
            ->getArrayResult();

        return $audience;
    }
}
