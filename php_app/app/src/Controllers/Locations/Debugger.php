<?php

/**
 * Created by jamieaitken on 09/02/2018 at 13:38
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations;

use App\Controllers\Integrations\Mikrotik\_MikrotikExportController;
use App\Controllers\Integrations\Radius\_RadiusCreationController;
use App\Controllers\Locations\Devices\_LocationsDevicesController;
use App\Models\Integrations\UniFi\UnifiController;
use App\Models\Integrations\UniFi\UnifiLocation;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Social\LocationSocial;
use App\Models\MikrotikConfig;
use App\Models\RadiusVendor;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class Debugger
{
	protected $em;
	protected $infrastructureCache;

	public function __construct(EntityManager $em)
	{
		$this->em                  = $em;
		$this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
	}

	public function hasConnectionsRoute(Request $request, Response $response)
	{

		$send = $this->hasConnections($request->getAttribute('serial'));

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function hasInformRoute(Request $request, Response $response)
	{

		$send = $this->hasInform($request->getAttribute('serial'));

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function canReceiveConfigRoute(Request $request, Response $response)
	{

		$send = $this->canReceiveConfig($request->getAttribute('serial'));

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function hasBeenSetupRoute(Request $request, Response $response)
	{

		$send = $this->hasBeenSetup($request->getAttribute('serial'));

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function hasFacebookPageIdRoute(Request $request, Response $response)
	{
		$send = $this->hasFacebookPageId($request->getAttribute('serial'));

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function hasConnections(string $serial)
	{
		$start = new \DateTime();
		$end   = new \DateTime();
		$start->modify('-1 day');

		$totals = $this->em->createQueryBuilder()
			->select('COUNT(ud.profileId) as totalConnections')
			->from(UserData::class, 'ud')
			->leftJoin(UserProfile::class, 'up', 'WITH', 'ud.profileId = up.id')
			->where('ud.serial IN (:serial)')
			->andWhere('ud.dataUp IS NOT NULL')
			->andWhere('ud.timestamp BETWEEN :start AND :end')
			->setParameter('start', $start)
			->setParameter('end', $end)
			->setParameter('serial', $serial)
			->getQuery()
			->getArrayResult();

		$newStatus = new DebuggerStatus();
		$newStatus->setToShow(true);

		if (!empty($totals)) {
			$connections = (int)$totals[0]['totalConnections'];
			$newStatus->setDescription('The device has had ' . $connections . ' Connections within the last day.');
			if ($connections > 0 && $connections < 25) {
				$newStatus->setCategory('danger-light');
			} elseif ($connections >= 25 && $connections < 50) {
				$newStatus->setCategory('warning');
			} elseif ($connections >= 50) {
				$newStatus->setCategory('success');
			}
		}

		$newStatus->setDebuggerKind('hasConnections');

		return Http::status(200, $newStatus->serialize());
	}

	public function hasInform(string $serial)
	{
		date_default_timezone_set('Europe/London');
		$status = new DebuggerStatus();

		$fetch = $this->em->getRepository(Inform::class)->findOneBy([
			'serial' => $serial
		]);

		$status->setDebuggerKind('hasInform');


		if (is_null($fetch)) {
			$status->setCategory('warning');
			$status->setToShow(false);

			return Http::status(200, $status->serialize());
		}

		$lastCheckIn = $fetch->timestamp;

		$cacheCheck = $this->infrastructureCache->fetch('informs:' . $serial);

		$date            = date('I');
		$daylightSavings = false;

		if ($date === '1') {
			$daylightSavings = true;
		}

		if (!is_bool($cacheCheck)) {
			if ($cacheCheck['timestamp'] > $lastCheckIn) {
				$lastCheckIn = $cacheCheck['timestamp'];
			}
		}

		if ($daylightSavings) {
			$lastCheckIn = $lastCheckIn->modify('+ 1 hour');
		}

		if ($fetch->vendor === 'RUCKUS-SMARTZONE') {
			$status->setToShow(false);
		}


		if (is_object($fetch)) {
			if ($fetch->status) {
				$status->setCategory('success');
			}

			$status->setDescription('Your ' . $fetch->vendor . ' device was last online at ' . $lastCheckIn->format('d/m/Y H:i:s') . ' GMT.');
		} else {
			$status->setDescription('This device has never informed.');
		}

		return Http::status(200, $status->serialize());
	}

	public function canReceiveConfig(string $serial)
	{
		$newStatus = new DebuggerStatus();
		$newStatus->setToShow(false);
		$newStatus->setDescription('This device can not receive a config');
		$newStatus->setDebuggerKind('hasConfig');

		$isMikrotikCheck = $this->em->createQueryBuilder()
			->select('u.serial')
			->from(Inform::class, 'u')
			->where('u.vendor = :mik')
			->andWhere('u.serial = :ser')
			->setParameter('mik', 'MIKROTIK')
			->setParameter('ser', $serial)
			->getQuery()
			->getArrayResult();

		if (empty($isMikrotikCheck)) {
			return Http::status(200, $newStatus->serialize());
		}

		$newStatus->setToShow(true);

		$newMikroTikExport = new _MikrotikExportController($this->em);
		$newMikroTikExport->export('exports@stampede.ai', $serial);

		$now         = new \DateTime();
		$fiveMinutes = new \DateTime();
		$fiveMinutes->modify('- 5 minutes');

		$check = $this->em->createQueryBuilder()
			->select('u')
			->from(MikrotikConfig::class, 'u')
			->where('u.serial = :s')
			->andWhere('u.createdAt BETWEEN :start AND :end')
			->setParameter('s', $serial)
			->setParameter('start', $fiveMinutes)
			->setParameter('end', $now)
			->getQuery()
			->getArrayResult();

		if (!empty($check)) {
			$newStatus->setDescription('This device can export a config');
			$newStatus->setCategory('success');
		}

		return Http::status(200, $newStatus->serialize());
	}

	public function hasBeenSetup(string $serial)
	{
		$locationVendor = new _LocationsDevicesController($this->em);
		$vendor         = strtoupper($locationVendor->getVendor($serial));

		$newStatus = new DebuggerStatus();
		$newStatus->setDebuggerKind('hasSetup');
		$radiusVendor = new _RadiusCreationController($this->em);
		if ($vendor === 'MIKROTIK') {
			$newStatus->setToShow(false);

			return Http::status(200, $newStatus->serialize());
		}

		if ($vendor === 'UNIFI') {
			$hasSecret = $this->em->createQueryBuilder()
				->select('n')
				->from(UnifiLocation::class, 'u')
				->join(UnifiController::class, 'n', 'WITH', 'u.unifiControllerId = n.id')
				->where('u.serial = :ser')
				->andWhere('n.hostname IS NULL')
				->andWhere('n.username IS NULL')
				->andWhere('n.password IS NULL')
				->setParameter('ser', $serial)
				->getQuery()
				->getArrayResult();
		} elseif ($radiusVendor->isRadiusVendor($vendor)) {
			$hasSecret = $this->em->createQueryBuilder()
				->select('u')
				->from(RadiusVendor::class, 'u')
				->where('u.serial = :s')
				->andWhere('u.secret IS NULL')
				->setParameter('s', $serial)
				->getQuery()
				->getArrayResult();
		}

		if (empty($hasSecret)) {
			$newStatus->setCategory('success');
			if ($vendor !== 'UNIFI') {
				$newStatus->setDescription('This ' . $hasSecret[0]['vendor'] . ' device has a secret associated with it.');
				$newStatus->setToShow(true);
			} else {
				$newStatus->setDescription('This UniFi device has a hostname, username and password associated with it.');
				$newStatus->setToShow(true);
			}
		} else {
			if (isset($hasSecret[0]['vendor'])) {
				if ($hasSecret[0]['vendor'] === 'MERAKI') {
					$newStatus->setCategory('success');
					$newStatus->setDescription('This Meraki device is setup correctly.');
					$newStatus->setToShow(true);
				} elseif ($hasSecret[0]['vendor'] === 'RUCKUS-SMARTZONE') {
					$newStatus->setToShow(false);
				} else {
					$newStatus->setCategory('danger');
					$newStatus->setDescription('This Radius device does not have a secret associated with it.');
					$newStatus->setToShow(true);
				}
			} elseif (isset($hasSecret[0]['hostname'])) {
				$newStatus->setCategory('danger');
				$newStatus->setDescription('This UniFi device does not have a hostname, username or password associated with it.');
				$newStatus->setToShow(true);
			}
		}

		return Http::status(200, $newStatus->serialize());
	}

	public function hasFacebookPageId(string $serial)
	{

		$newStatus = new DebuggerStatus();
		$newStatus->setToShow(false);
		$newStatus->setDebuggerKind('hasFacebook');


		$socialId = $this->em->createQueryBuilder()
			->select('u.facebook')
			->from(LocationSettings::class, 'u')
			->where('u.serial = :ser')
			->setParameter('ser', $serial)
			->getQuery()
			->getArrayResult()[0]['facebook'];

		if (empty($socialId)) {

			$newStatus->setDescription('You do not have a Social ID associated with your location');

			return Http::status(404, $newStatus->serialize());
		}

		$facebookPageId = $this->em->createQueryBuilder()
			->select('u.page, u.enabled')
			->from(LocationSocial::class, 'u')
			->where('u.id = :i')
			->setParameter('i', $socialId)
			->getQuery()
			->getArrayResult()[0];

		if (!$facebookPageId['enabled']) {

			$newStatus->setDescription('You do not have Facebook check-in enabled');

			return Http::status(200, $newStatus->serialize());
		}

		$newStatus->setToShow(true);

		$curl = new Curl();
		$curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
		$curl->get('http://facebook.com/' . $facebookPageId['page']);

		$dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$dom->strictErrorChecking = false;
		$dom->loadHTML($curl->response);

		$pageTitle = $dom->getElementById('pageTitle');

		if (is_null($pageTitle)) {
			$newStatus->setDescription('Page ID is not valid');

			return Http::status(409, $newStatus->serialize());
		}

		if ($pageTitle->textContent === 'Facebook') {
			$newStatus->setDescription('Page ID is not valid');

			return Http::status(409, $newStatus->serialize());
		}

		$newStatus->setCategory('success');
		$newStatus->setDescription('Your Location is currently linked to ' . $pageTitle->textContent);

		return Http::status(200, $newStatus->serialize());
	}
}
