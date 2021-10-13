<?php


namespace App\Package\Async;


class NopPinger implements Pinger
{

    public function ping(){}
}