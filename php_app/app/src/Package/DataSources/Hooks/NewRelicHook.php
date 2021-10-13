<?php


namespace App\Package\DataSources\Hooks;


class NewRelicHook implements Hook
{

    public function notify(Payload $payload): void
    {
        newrelic_record_custom_event(
            "InteractionCreate",
            [
                'id'   => $payload->getInteraction()->getId()->toString(),
                'type' => $payload->getDataSource()->getKey(),
            ]
        );
    }
}