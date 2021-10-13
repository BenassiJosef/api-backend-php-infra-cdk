<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 15/02/2017
 * Time: 15:11
 */

namespace App\Controllers\Integrations\Slack;

use Curl\Curl;

class _SlackWebhookController
{
    private $slackChannels = [
        'connect'     => 'https://hooks.slack.com/services/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',
        'signup'      => 'https://hooks.slack.com/services/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',
        'feature' => 'https://hooks.slack.com/services/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'
    ];

    private $slackChannel;

    public function __construct(string $slackChannel)
    {
        $this->slackChannel = $this->slackChannels[$slackChannel];
    }

    public function slackMessage(array $payload)
    {

        $curl = new Curl();
        $curl->post($this->slackChannel, json_encode($payload));

        return $curl->response;
    }
}
