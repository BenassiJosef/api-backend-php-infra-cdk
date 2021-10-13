<?php
/**
 * Created by jamieaitken on 22/01/2019 at 14:22
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\LocationSearch;


use Doctrine\ORM\EntityManager;

class SearchableLocation implements ILocationSearch
{

    private $locationType = null;
    private $businessType = null;
    private $address = null;
    private $postCode = null;
    private $town = null;
    private $offset = null;
    private $accessibleSerials = [];

    protected $entityManager;

    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    public function isSearchingLocationType(array $queryParams)
    {
        if (isset($queryParams['type'])) {
            $this->locationType = (int)$queryParams['type'];

            return true;
        }

        return false;
    }

    public function getLocationType()
    {
        return $this->locationType;
    }

    public function isSearchingBusinessType(array $queryParams)
    {
        if (isset($queryParams['locationType'])) {
            $this->locationType = $queryParams['locationType'];

            return true;
        }

        return false;
    }

    public function getBusinessType()
    {
        return $this->businessType;
    }

    public function isSearchingAddress(array $queryParams)
    {
        if (isset($queryParams['address'])) {

            $this->address = $queryParams['address'];

            return true;
        }

        return false;
    }

    public function isSearchingPostCode(array $queryParams)
    {
        if (isset($queryParams['postcode'])) {

            $this->postCode = $queryParams['postcode'];

            return true;
        }

        return false;
    }

    public function isSearchingTown(array $queryParams)
    {
        if (isset($queryParams['town'])) {

            $this->town = $queryParams['town'];

            return true;
        }

        return false;
    }

    public function isOffsetPresent(array $queryParams)
    {
        if (isset($queryParams['offset'])) {

            $this->offset = (int)$queryParams['offset'];

            return true;
        }

        return false;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getPostCode()
    {
        return $this->postCode;
    }

    public function getTown()
    {
        return $this->town;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function prepareBaseStatement()
    {
        throw new \Exception("MUST_BE_OVERRIDDEN", 501);
    }

    public function getSerials()
    {
        return $this->accessibleSerials;
    }

    public function setSerials(array $accessibleSerials)
    {
        $this->accessibleSerials = $accessibleSerials;
    }
}