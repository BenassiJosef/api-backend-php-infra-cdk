<?php
/**
 * Created by jamieaitken on 05/12/2018 at 17:23
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\SQS;


use Aws\Sqs\SqsClient;
use GuzzleHttp\Psr7\Uri;
use http\Url;
use Psr\Http\Message\UriInterface;

class QueueSender
{

    /**
     * @var SqsClient $client
     */
    protected $client;

    public function __construct()
    {
        $config = [
            'region'      => 'eu-west-1',
            'version'     => 'latest',
            'credentials' => [
                'key'    => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY')
            ]
        ];

        $sqsEndpoint = getenv('SQS_ENDPOINT');
        if ($sqsEndpoint !== false) {
            $config['endpoint'] = $sqsEndpoint;
        }

        $this->client = new SqsClient($config);
    }

    /**
     * As our Queue URLs are hardcoded, we need to map them on to the
     * endpoint that our SQS client uses, rather than using the ones
     * that are hardcoded.
     *
     * This is allows us to run the backend service locally, allowing
     * us to test endpoints in a more representative manner.
     *
     * @param string $queueUrl
     * @return string
     */
    private function mapUrlToClientEndpoint(string $queueUrl): string
    {
        $sqsEndpoint = $this->client->getEndpoint();
        $queueUri    = new Uri($queueUrl);
        $newUrl      = $sqsEndpoint
            ->withPath($queueUri->getPath());
        return $newUrl->__toString();
    }

    public function sendMessage($dataToBeSent, string $queueUrl)
    {
        if (is_array($dataToBeSent)) {
            $dataToBeSent = json_encode($dataToBeSent);
        }

        $url  = $this->mapUrlToClientEndpoint($queueUrl);
        $args = [
            'MessageBody' => $dataToBeSent,
            'QueueUrl'    => $url,
        ];

        $this->client->sendMessage($args);
        $this->sentMessage($queueUrl);
    }

    private function sentMessage(string $url)
    {
        if (extension_loaded('newrelic')) {
            newrelic_record_custom_event(
                'SQSMessageSent',
                [
                    'url' => $url,
                ]
            );
        }
    }

}