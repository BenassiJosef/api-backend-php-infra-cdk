<?php

/**
 * Created by jamieaitken on 12/02/2019 at 16:29
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Vendor;


use App\Controllers\Integrations\IgniteNet\_IgniteNetCreationController;
use App\Controllers\Integrations\Mikrotik\MikrotikCreationController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\Radius\_RadiusCreationController;
use App\Controllers\Integrations\UniFi\_UniFiCreationController;
use App\Models\Locations\Informs\Inform;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;

class VendorSwap
{
	private $serial;
	private $latestVendor;
	private $currentVendor;
	private $em;
	private $infrastructureCache;
	private $nearlyCache;
	private $mp;

	public function __construct(string $serial, string $latestVendor, EntityManager $entityManager)
	{
		$this->serial              = $serial;
		$this->mp                  = new _Mixpanel();
		$this->latestVendor        = strtolower($latestVendor);
		$this->em                  = $entityManager;
		$this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
		$this->nearlyCache         = new CacheEngine(getenv('NEARLY_REDIS'));
	}

	public function setCurrentVendor()
	{
		$vendor = $this->em->createQueryBuilder()
			->select('i.vendor')
			->from(Inform::class, 'i')
			->where('i.serial = :serial')
			->setParameter('serial', $this->serial)
			->getQuery()
			->getArrayResult()[0]['vendor'];

		$this->currentVendor = strtolower($vendor);
	}

	public function isChangeRequired()
	{
		if ($this->currentVendor !== $this->latestVendor) {
			return true;
		}

		return false;
	}

	public function executeSwap()
	{
		$current  = null;
		$changeTo = null;
		$radCheck = new _RadiusCreationController($this->em);
		if ($this->currentVendor === 'mikrotik') {
			$current = new MikrotikCreationController($this->em, null, null, null, null);
		} elseif ($this->currentVendor === 'unifi') {
			$current = new _UniFiCreationController($this->em);
		} elseif ($this->currentVendor === 'ignitenet') {
			$current = new _IgniteNetCreationController($this->em);
		} elseif ($radCheck->isRadiusVendor($this->currentVendor)) {
			$current = $radCheck;
		}

		if (is_null($current)) {
			$this->mp->track('execute_swap_failed', [
				'serial'              => $this->serial,
				'vendorFromChargeBee' => $this->latestVendor
			]);
		}

		if ($this->latestVendor === 'mikrotik') {
			$changeTo = new MikrotikCreationController($this->em, null, null, null, null);
		} elseif ($this->latestVendor === 'unifi') {
			$changeTo = new _UniFiCreationController($this->em);
		} elseif ($this->latestVendor === 'ignitenet') {
			$changeTo = new _IgniteNetCreationController($this->em);
		} elseif ($radCheck->isRadiusVendor($this->latestVendor)) {
			$changeTo = new _RadiusCreationController($this->em);
		}

		if (!is_null($current)) {
			$current->deleteBespokeLogic($this->serial, $this->currentVendor);
		}
		$changeTo->createBespokeLogic($this->serial, $this->latestVendor);
		$this->deleteVendorCache();
		$this->changeTypeInInform();
	}

	private function changeTypeInInform()
	{
		$this->em->createQueryBuilder()
			->update(Inform::class, 'i')
			->set('i.vendor', ':vendor')
			->where('i.serial = :serial')
			->setParameter('vendor', strtoupper($this->latestVendor))
			->setParameter('serial', $this->serial)
			->getQuery()
			->execute();
	}

	private function deleteVendorCache()
	{
		$this->infrastructureCache->delete('informs:' . $this->serial);
		$this->nearlyCache->delete($this->serial . ':vendor');
	}
}
