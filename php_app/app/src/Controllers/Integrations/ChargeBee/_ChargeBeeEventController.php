<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 30/06/2017
 * Time: 15:51
 */

namespace App\Controllers\Integrations\ChargeBee;

class _ChargeBeeEventController implements ChargeBeeEventGetter
{
    protected $eventHandler;

    public function __construct()
    {
        $this->eventHandler = new _ChargeBeeHandleErrors();
    }

    /**
     * @param string $id
     * @param array $event
     * @return array
     */
    public function getEvent(string $id, array $event = [])
    {
        $chargeBeeEvent = function ($id) {
            return \ChargeBee_Event::retrieve($id)
                ->event()->getValues();
        };

        return $this->eventHandler->handleErrors($chargeBeeEvent, $id);
    }
}
