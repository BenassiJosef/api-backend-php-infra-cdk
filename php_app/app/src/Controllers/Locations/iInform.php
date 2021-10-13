<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 15/03/2017
 * Time: 15:52
 */

namespace App\Controllers\Locations;

interface iInform
{
    public function createInform(string $serial, string $ip, string $vendor, array $extraInformData);

    public function getInform(string $serial);

    public function setInform(string $serial, array $dataset);

    public function getFromCache(string $serial);

    public function saveToCache(string $serial, array $dataset);

    public function getFromPersistentStorage(string $serial);

    public function saveToPersistentStorage(string $serial, array $dataset);
}
