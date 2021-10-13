<?php
/**
 * Created by jamieaitken on 22/01/2019 at 12:45
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\LocationSearch;


interface ILocationSearch
{
    public function prepareBaseStatement();

    public function isSearchingLocationType(array $queryParams);

    public function getLocationType();

    public function isSearchingBusinessType(array $queryParams);

    public function getBusinessType();

    public function isSearchingAddress(array $queryParams);

    public function getAddress();

    public function isSearchingPostCode(array $queryParams);

    public function getPostCode();

    public function isSearchingTown(array $queryParams);

    public function getTown();

    public function isOffsetPresent(array $queryParams);

    public function getOffset();

    public function setSerials(array $accessibleSerials);

    public function getSerials();
}