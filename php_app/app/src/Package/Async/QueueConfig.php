<?php


namespace App\Package\Async;

use Aws\Sqs\SqsClient;
use GuzzleHttp\Psr7\Uri;
use JsonSerializable;

class QueueConfig implements JsonSerializable
{
    /**
     * @Inject("QueueURL")
     * @var string $url
     */
    private $url;

    /**
     * @var string $region
     */
    private $region;

    /**
     * @var string | null $key
     */
    private $key;

    /**
     * @var string | null $secret
     */
    private $secret;

    /**
     * @var string | null $endpoint
     */
    private $endpoint;

    /**
     * @Inject("WaitTimeout")
     * @var int
     */
    private $waitTimeout;

    /**
     * @var SqsClient | null $client
     */
    private $client = null;

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['url'],
            $data['region'],
            $data['key'],
            $data['secret'],
            $data['endpoint'],
            $data['waitTimeout']
        );
    }

    /**
     * QueueConfig constructor.
     * @param string $url
     * @param string|null $region
     * @param string|null $key
     * @param string|null $secret
     * @param string|null $endpoint
     * @param int|null $waitTimeout
     */
    public function __construct(
        ?string $url = null,
        ?string $region = null,
        ?string $key = null,
        ?string $secret = null,
        ?string $endpoint = null,
        ?int $waitTimeout = null
    ) {
        if ($url !== null) {
            $this->url = $url;
        }
        $this->region      = $region ?? $this->envString('AWS_REGION') ?? 'eu-west-1';
        $this->key         = $key ?? $this->envString('AWS_ACCESS_KEY_ID');
        $this->secret      = $secret ?? $this->envString('AWS_SECRET_ACCESS_KEY');
        $this->endpoint    = $endpoint ?? $this->envString('SQS_ENDPOINT');
        $this->waitTimeout = $waitTimeout ?? $this->envInt('SQS_WAIT_TIMEOUT') ?? 5;
    }

    private function mapUrlToClientEndpoint(string $queueUrl): string
    {
        $sqsEndpoint = $this->client->getEndpoint();
        $queueUri    = new Uri($queueUrl);
        $newUrl      = $sqsEndpoint
            ->withPath($queueUri->getPath());
        return $newUrl->__toString();
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        $sqsEndpoint = $this->client->getEndpoint();
        $queueUri    = new Uri($this->url);
        $newUrl      = $sqsEndpoint
            ->withPath($queueUri->getPath());
        return $newUrl->__toString();
    }

    private function envString(string $name): ?string
    {
        $var = getenv($name);
        if (!$var) {
            return null;
        }
        return $var;
    }

    private function envInt(string $name): ?int
    {
        $var = getenv($name);
        if (!$var) {
            return null;
        }
        return intval($var);
    }

    /**
     * @return int
     */
    public function getWaitTimeout(): int
    {
        return $this->waitTimeout;
    }

    public function client(): SqsClient
    {
        if ($this->client !== null) {
            return $this->client;
        }
        $config = [
            'region'  => $this->region,
            'version' => 'latest',
        ];

        if ($this->secret !== null) {
            $config['credentials'] = [
                'key'    => $this->key,
                'secret' => $this->secret,
            ];
        }

        if ($this->endpoint !== null) {
            $config['endpoint'] = $this->endpoint;
        }
        $this->client = new SqsClient(
            $config
        );
        return $this->client;
    }

    public function jsonSerialize()
    {
        return [
            'url'         => $this->url,
            'region'      => $this->region,
            'key'         => $this->key,
            'secret'      => $this->secret,
            'endpoint'    => $this->endpoint,
            'waitTimeout' => $this->waitTimeout,
        ];
    }
}