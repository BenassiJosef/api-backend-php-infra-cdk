<?php

namespace App\Package\Location;

use App\Models\Locations\LocationSettings;
use App\Package\Vendors\Information;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class LocationController
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;


	/**
	 * @var Information $information
	 */
	private $information;

	/**
	 * LocationController constructor.
	 * @param EntityManager $entityManager

	 */
	public function __construct(
		EntityManager $entityManager
	) {
		$this->entityManager = $entityManager;
		$this->information = new Information($this->entityManager);
	}

	public function get(Request $request, Response $response)
	{
		$serial = $request->getAttribute('serial', null);
		if (is_null($serial) || strlen($serial) !== 12) {
			return $response->withJson('SERIAL_INVALID', 403);
		}

		/**
		 * @var LocationSettings $location
		 */
		$location = $this->entityManager->getRepository(LocationSettings::class)->findOneBy(['serial' => $serial]);

		if (is_null($location)) {
			return $response->withJson('NO_LOCATION_FOUND', 403);
		}

		$inform = $this->information->getFromSerial($serial);
		$res = $location->jsonSerializeMapped();
		if (!is_null($inform)) {
			$res['inform'] = $inform->jsonSerialize();
		}

		return $response->withJson($res, 200);
	}
}
