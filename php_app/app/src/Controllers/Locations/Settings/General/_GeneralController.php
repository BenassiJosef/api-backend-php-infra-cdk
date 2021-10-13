<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 02/05/2017
 * Time: 17:18
 */

namespace App\Controllers\Locations\Settings\General;

use App\Models\Locations\LocationSettings;
use App\Models\Locations\Templating\LocationTemplate;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _GeneralController
{
	protected $em;
	protected $connectCache;
	protected $nearlyCache;

	function __construct(EntityManager $em)
	{
		$this->em           = $em;
		$this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
		$this->nearlyCache  = new CacheEngine(getenv('NEARLY_REDIS'));
	}

	public function getRoute(Request $request, Response $response)
	{

		$send = $this->get($request->getAttribute('serial'));

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function updateRoute(Request $request, Response $response)
	{

		$send = $this->update(
			$request->getAttribute('serial'),
			$request->getParsedBody(),
			$request->getAttribute('user')
		);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function get(string $serial)
	{
		$getSettings = $this->em->createQueryBuilder()
			->select('u.alias, u.url, u.language, u.type, u.translation, u.demoData')
			->from(LocationSettings::class, 'u')
			->where('u.serial = :s')
			->setParameter('s', $serial)
			->getQuery()
			->getArrayResult();

		if (empty($getSettings)) {
			return Http::status(200, []);
		}

		return Http::status(200, $getSettings[0]);
	}

	public function update(string $serial, array $update, array $user)
	{
		$allowedKeys = ['alias', 'url', 'language', 'type', 'translation'];

		$network = $this->em->getRepository(LocationSettings::class)->findOneBy([
			'serial' => $serial
		]);

		foreach ($update as $key => $value) {
			if (in_array($key, $allowedKeys)) {
				$network->$key = $value;
			}
		}
		$this->em->flush();

		$sitesUsingThisAsTemplate = $this->em->createQueryBuilder()
			->select('u.serial')
			->from(LocationTemplate::class, 'u')
			->where('u.serialCopyingFrom = :ser')
			->setParameter('ser', $serial)
			->getQuery()
			->getArrayResult();

		$removeFromConnectCache = [];
		$removeFromNearlyCache  = [];

		foreach ($sitesUsingThisAsTemplate as $key => $site) {
			$removeFromNearlyCache[]  = $site['serial'] . ':landingPage';
			$removeFromConnectCache[] = $user['uid'] . ':menus:' . $site['serial'];
		}

		$removeFromConnect = array_merge(
			[
				$user['uid'] . ':menus:' . $serial,
				$user['uid'] . ':location:accessibleLocations',
				$user['uid'] . ':marketing:accessibleLocations'
			],
			$removeFromConnectCache
		);

		$removeFromNearly = array_merge([$serial . ':landingPage'], $removeFromNearlyCache);

		$this->connectCache->deleteMultiple($removeFromConnect);
		$this->nearlyCache->deleteMultiple($removeFromNearly);


		return Http::status(200, $network->getArrayCopy());
	}
}
