<?php


namespace App\Package\DataSources\Hooks;


class LoggingHook implements Hook
{

    public function notify(Payload $payload): void
    {
        $dataSource = $payload->getDataSource()->getName();
        $email = $payload->getUserProfile()->getEmail();
        error_log("${dataSource} - ${email}");
    }
}