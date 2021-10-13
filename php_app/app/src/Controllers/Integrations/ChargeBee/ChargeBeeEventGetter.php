<?php


namespace App\Controllers\Integrations\ChargeBee;


interface ChargeBeeEventGetter
{

    /**
     * @param string $id
     * @param array $event
     * @return mixed
     */
    public function getEvent(string $id, array $event = []);
}