<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/05/2017
 * Time: 12:53
 */

namespace App\Controllers\Locations\Reports;

use App\Models\Locations\LocationSettings;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Models\UserRegistration;
use Doctrine\ORM\Tools\Pagination\Paginator;

class _CustomerReportController extends ReportController implements IReport
{
	private $defaultOrder = 'ur.lastSeenAt';
	private $exportHeaders = [];

	public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
	{
		$maxResults = 50;
		$allTypes   = ['origin', 'gender'];
		$sql        = '
        up.id,
        up.first,
        up.last,
        up.phone,
        up.email, 
        up.opt,
        ur.createdAt as join_date,
        MAX(ud.timestamp) as connectedAt,
        MAX(ud.lastupdate) as lastseenAt,
        up.ageRange,
        UPPER(up.postcode) as postcode,
        ud.serial,
        up.birthMonth,
        up.birthDay, 
        up.country,
        up.verified,
        COALESCE(SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)), 0) as timespent,
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload, 
        COUNT(ud.profileId) as totalConnections,
        up.custom';


		$settings = $this->em->createQueryBuilder()
			->select('u.freeQuestions')
			->from(LocationSettings::class, 'u')
			->where('u.serial = :serial')
			->setParameter(':serial', $serial[0])
			->getQuery()
			->getArrayResult()[0];

		if (strpos(implode(',', $settings['freeQuestions']), 'Gender') !== false) {
			$sql .= ', up.gender';
		}

		$append = false;
		if (isset($options['type'])) {
			if (in_array($options['type'], $allTypes)) {
				$append = true;
				switch ($options['type']) {
					case 'origin':
						$sql .= ', up.lat, up.lng';
						break;
				}
			}
		}

		$people = $this->em->createQueryBuilder()
			->select($sql)
			->from(UserRegistration::class, 'ur')
			->leftJoin(UserProfile::class, 'up', 'WITH', 'ur.profileId = up.id')
			->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId')
			->where('ur.serial IN (:serial)')
			->andWhere('ur.lastSeenAt BETWEEN :start AND :end')
			->andWhere('ud.dataUp IS NOT NULL')
			->setParameter('serial', $serial)
			->setParameter('start', $start)
			->setParameter('end', $end)
			->orderBy($this->defaultOrder, $options['sort'])
			->groupBy('ur.profileId');

		if ($append == false) {
			$people
				->setFirstResult($options['offset'])
				->setMaxResults($maxResults);
		}

		$results = new Paginator($people);
		$results->setUseOutputWalkers(false);

		$people = $results->getIterator()->getArrayCopy();


		if (!empty($people)) {
			$newRegistrationReportController = new _RegistrationReportController($this->em);
			$customQuestions                 = $newRegistrationReportController->fetchHeadersForSerial($serial[0]);

			foreach ($customQuestions as $key => $customQuestion) {
				if (!isset($customQuestion['id'])) {
					continue;
				}

				foreach ($people as $k => $person) {
					if (!isset($person['custom'][$serial[0]])) {
						continue;
					}
					foreach ($person['custom'][$serial[0]] as $qK => $item) {
						if ($qK === $customQuestion['id']) {
							$people[$k][$customQuestion['id']] = $item;
						}
					}
				}
			}

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


			foreach ($people as $key => $person) {
				if (is_null($person['email']) || empty($person['email']) || strpos($person['email'], '@') === false) {
					foreach ($person as $k => $v) {
						if (in_array($k, $keysToHide)) {
							$people[$key][$k] = 'OPT_OUT';
						}
					}
				} else {
					foreach ($person as $k => $v) {
						if (is_numeric($v)) {
							if ($k === 'lat' || $k === 'lng') {
								$people[$key][$k] = (float)$v;
							} elseif ($k !== 'phone') {
								$people[$key][$k] = (int)round($v);
							}
						}
					}
					unset($people[$key]['custom']);
				}
			}

			$return = [
				'table'       => $people,
				'has_more'    => false,
				'total'       => count($results),
				'next_offset' => $options['offset'] + $maxResults
			];

			if ($options['offset'] <= $return['total'] && count($people) !== $return['total']) {
				$return['has_more'] = true;
			}

			return $return;
		}

		return [];
	}

	public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
	{

		$sql = '
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload, 
        COUNT(ud.profileId) as totalConnections, 
        COUNT(DISTINCT(ud.profileId)) as uniqueConnections,
        SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime,
        COALESCE(AVG(ud.dataUp),0) as averageUp,
        COALESCE(AVG(ud.dataDown),0) as averageDown, 
        AVG(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as averageUptime,
        UNIX_TIMESTAMP(ud.timestamp) as timestamp';

		if ($options['grouping']['group'] === 'hours') {
			$sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month, DAY(ud.timestamp) as day, HOUR(ud.timestamp) as hour';
			$group = 'year, month, day, hour';
		} elseif ($options['grouping']['group'] === 'days') {
			$sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month, DAY(ud.timestamp) as day';
			$group = 'year, month, day';
		} elseif ($options['grouping']['group'] === 'weeks') {
			$sql   .= ', YEAR(ud.timestamp) as year, WEEK(ud.timestamp) as week';
			$group = 'year, week';
		} elseif ($options['grouping']['group'] === 'months') {
			$sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month';
			$group = 'year, month';
		}

		$totals = $this->em->createQueryBuilder()
			->select($sql)
			->from(UserData::class, 'ud')
			->where('ud.serial IN (:serial)')
			->andWhere('ud.timestamp BETWEEN :start AND :end')
			->andWhere('ud.dataUp IS NOT NULL')
			->setParameter('start', $start)
			->setParameter('end', $end)
			->setParameter('serial', $serial)
			->groupBy($group)
			->orderBy('ud.timestamp', $options['sort'])
			->getQuery()
			->getArrayResult();

		if (!empty($totals)) {
			foreach ($totals as $key => $connection) {
				foreach ($connection as $k => $v) {
					if (is_numeric($v)) {
						$totals[$key][$k] = (int)round($v);
					}
				}
			}

			return $totals;
		}

		return [];
	}

	public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
	{
		$allTypes = ['origin'];
		$male     = 0;
		$female   = 0;
		$other    = 0;

		$genderQuery = $this->em->createQueryBuilder()
			->select('
            up.gender as gender,
            COUNT(DISTINCT(ud.profileId)) AS logins')
			->from(UserData::class, 'ud')
			->leftJoin(UserProfile::class, 'up', 'WITH', 'up.id = ud.profileId')
			->where('ud.serial = :serial')
			->andWhere('ud.dataUp IS NOT NULL')
			->andWhere('ud.timestamp BETWEEN :start AND :end')
			->setParameter('serial', $serial)
			->setParameter('start', $start)
			->setParameter('end', $end)
			->groupBy('gender')
			->getQuery()
			->getArrayResult();
		if (!empty($genderQuery)) {
			foreach ($genderQuery as $key => $gender) {
				if ($gender['gender'] === 'f') {
					$female = $gender['logins'];
				}
				if ($gender['gender'] === 'm') {
					$male = $gender['logins'];
				}
				if ($gender['gender'] === 'o') {
					$other = $gender['logins'];
				}
			}
		}


		$sql = "
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload, 
        COUNT(DISTINCT(ud.profileId)) as customers, 
        COALESCE(SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)), 0) as timespent,
        COALESCE(AVG(ud.dataUp),0) as averageUp, 
        COUNT(DISTINCT ud.profileId) as uniqueUsers,
        COUNT(DISTINCT up.id) as registrations,
        COALESCE(AVG(ud.dataDown),0) as averageDown,
        AVG(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as averageTime,
        COUNT(ud.profileId) as totalConnections";

		if (isset($options['type'])) {
			if (in_array($options['type'], $allTypes)) {
				switch ($options['type']) {
					case 'origin':
						$sql .= ', up.lat, up.lng';
						break;
				}
			}
		}

		$returnsUsers = $this->em->createQueryBuilder()
			->select('u.profileId')
			->from(UserData::class, 'u')
			->where('u.serial IN (:serial)')
			->andWhere('u.dataUp IS NOT NULL')
			->andWhere('u.timestamp BETWEEN :start AND :end')
			->setParameter('serial', $serial)
			->setParameter('start', $start)
			->setParameter('end', $end)
			->groupBy('u.profileId')
			->having('COUNT(u) > 1')
			->getQuery()
			->getArrayResult();


		$totals = $this->em->createQueryBuilder()
			->select($sql)
			->from(UserData::class, 'ud')
			->leftJoin(UserProfile::class, 'up', 'WITH', 'up.id = ud.profileId')
			->where('ud.serial IN (:serial)')
			->andWhere('ud.dataUp IS NOT NULL')
			->andWhere('ud.timestamp BETWEEN :start AND :end')
			->setParameter('serial', $serial)
			->setParameter('start', $start)
			->setParameter('end', $end)
			->orderBy('up.timestamp', $options['sort'])
			->getQuery()
			->getArrayResult();

		if (!empty($totals)) {
			$totals[0]['return'] = sizeof($returnsUsers);
			$totals[0]['male']   = $male;
			$totals[0]['female'] = $female;
			$totals[0]['other']  = $other;
			foreach ($totals as $key => $gender) {
				foreach ($gender as $k => $v) {
					if (is_numeric($v)) {
						$totals[0][$k] = (int)round($v);
					}
				}
			}

			return $totals[0];
		}

		return [];
	}

	public function export(array $serial, \DateTime $start, \DateTime $end, array $options)
	{
		$allTypes = ['origin', 'gender'];
		$sql      = '
        up.id,
        up.first,
        up.last,
        up.phone,
        up.email, 
        up.opt,
        up.timestamp as join_date,
        MAX(ud.timestamp) as connectedAt,
        MAX(ud.lastupdate) as lastseenAt,
        up.ageRange,
        UPPER(up.postcode) as postcode,
        ur.serial,
        up.birthMonth,
        up.birthDay,
        up.country,
        up.verified,
        COALESCE(SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)), 0) as timespent,
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload, 
        COUNT(ud.profileId) as totalConnections,
        up.custom';


		$settings = $this->em->createQueryBuilder()
			->select('u.freeQuestions')
			->from(LocationSettings::class, 'u')
			->where('u.serial = :serial')
			->setParameter(':serial', $serial[0])
			->getQuery()
			->getArrayResult()[0];

		if (strpos(implode(',', $settings['freeQuestions']), 'Gender') !== false) {
			$sql .= ', up.gender';
		}

		$people = $this->em->createQueryBuilder()
			->select($sql)
			->from(UserRegistration::class, 'ur')
			->leftJoin(UserProfile::class, 'up', 'WITH', 'ur.profileId = up.id')
			->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId')
			->where('ur.serial IN (:serial)')
			->andWhere('ur.lastSeenAt BETWEEN :start AND :end')
			->andWhere('ud.dataUp IS NOT NULL')
			->setParameter('serial', $serial)
			->setParameter('start', $start)
			->setParameter('end', $end)
			->orderBy($this->defaultOrder, $options['sort'])
			->groupBy('ud.profileId');

		$results = new Paginator($people);
		$results->setUseOutputWalkers(false);

		$people = $results->getIterator()
			->getArrayCopy();


		if (!empty($people)) {
			$newRegistrationReportController = new _RegistrationReportController($this->em);
			$customQuestions                 = $newRegistrationReportController->fetchHeadersForSerial($serial[0]);

			foreach ($customQuestions as $key => $customQuestion) {
				if (!isset($customQuestion['id'])) {
					continue;
				}

				foreach ($people as $k => $person) {
					if (!isset($person['custom'][$serial[0]])) {
						continue;
					}
					foreach ($person['custom'][$serial[0]] as $qK => $item) {
						if ($qK === $customQuestion['id']) {
							$people[$k][$customQuestion['id']] = $item;
						}
					}
				}
			}

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


			foreach ($people as $key => $person) {
				if (is_null($person['email']) || empty($person['email']) || strpos($person['email'], '@') === false) {
					foreach ($person as $k => $v) {
						if (in_array($k, $keysToHide)) {
							$people[$key][$k] = 'OPT_OUT';
						}
					}
				} else {
					foreach ($person as $k => $v) {
						if (is_numeric($v)) {
							if ($k === 'lat' || $k === 'lng') {
								$people[$key][$k] = (float)$v;
							} elseif ($k !== 'phone') {
								$people[$key][$k] = (int)round($v);
							}
						}
					}
					unset($people[$key]['custom']);
				}
			}


			$this->exportHeaders = [
				'Id',
				'First Name',
				'Last Name',
				'Phone',
				'Email',
				'Opt-In',
				'Join Date',
				'Connected At',
				'Last Seen At',
				'Age Range',
				'Postcode',
				'Serial',
				'Birth Month',
				'Birth Day',
				'Country',
				'Verified',
				'Time Spent',
				'Total Uploaded',
				'Total Downloaded',
				'Total Connections'
			];

			if (array_key_exists('gender', $people[0])) {
				$this->exportHeaders[] = 'Gender';
			}

			if (array_key_exists('lat', $people[0])) {
				$this->exportHeaders[] = 'Latitude';
			}

			if (array_key_exists('lng', $people[0])) {
				$this->exportHeaders[] = 'Longitude';
			}

			foreach ($customQuestions as $key => $customQuestion) {
				if (isset($customQuestion['id'])) {
					$this->exportHeaders[] = $customQuestion['question'];
				}
			}


			return $people;
		}

		return [];
	}

	public function getDefaultOrder(): string
	{
		return $this->defaultOrder;
	}

	public function getExportHeaders()
	{
		return $this->exportHeaders;
	}

	public function setDefaultOrder(array $options)
	{
		if (isset($options['order'])) {
			$this->defaultOrder = $options['order'];
		}
	}
}
