<?php


namespace App\Package\DataSources\Hooks;


interface Hook
{
    public function notify(Payload $payload): void;
}