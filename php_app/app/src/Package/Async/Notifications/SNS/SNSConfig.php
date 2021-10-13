<?php

namespace App\Package\Async\Notifications\SNS;

use Aws\Sns\SnsClient;

/**
 * Class SNSConfig
 * @package App\Package\Async\Notifications\SNS
 */
class SNSConfig
{
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
     * SNSConfig constructor.
     * @param string|null $region
     * @param string|null $key
     * @param string|null $secret
     * @param string|null $endpoint
     */
    public function __construct(
        ?string $region = null,
        ?string $key = null,
        ?string $secret = null,
        ?string $endpoint = null
    ) {
        $this->region   = $region ?? $this->envString('AWS_REGION') ?? 'eu-west-1';
        $this->key      = $key ?? $this->envString('AWS_ACCESS_KEY_ID');
        $this->secret   = $secret ?? $this->envString('AWS_SECRET_ACCESS_KEY');
        $this->endpoint = $endpoint ?? $this->envString('SNS_ENDPOINT');
    }

    /**
     * @param string $name
     * @return string|null
     */
    private function envString(string $name): ?string
    {
        $var = getenv($name);
        if (!$var) {
            return null;
        }
        return $var;
    }

    /**
     * @return array
     */
    private function config(): array
    {
        $config = [
            'version' => 'latest',
            'region'  => $this->region,
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
        return $config;
    }

    /**
     * @return SnsClient
     */
    public function client(): SnsClient
    {
        return new SnsClient($this->config());
    }
}