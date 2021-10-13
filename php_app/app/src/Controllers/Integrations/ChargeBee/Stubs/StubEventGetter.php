<?php


namespace App\Controllers\Integrations\ChargeBee\Stubs;


use App\Controllers\Integrations\ChargeBee\ChargeBeeEventGetter;

class StubEventGetter implements ChargeBeeEventGetter
{
    /**
     * @param string $id
     * @param array $event
     * @return array
     */
    public function getEvent(string $id, array $event = [])
    {
        return [
            "status"  => 200,
            "message" => $event
        ];
    }
}