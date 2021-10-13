<?php


namespace App\Package\Clients\InternalOAuth;


use App\Package\Clients\InternalOAuth\Exceptions\InvalidConfigException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class ClientCredentialsConfig
{
    /**
     * @var string $clientId
     */
    private $clientId;

    /**
     * @var string $clientSecret
     */
    private $clientSecret;

    /**
     * @var string $tokenUrl
     */
    private $tokenUrl;

    /**
     * @var string $scope
     */
    private $scope;

    /**
     * ClientCredentialsConfig constructor.
     * @param string | null $clientId
     * @param string | null $clientSecret
     * @param string | null $tokenUrl
     * @param string | null $scope
     * @throws InvalidConfigException
     */
    public function __construct(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $tokenUrl = null,
        ?string $scope = null
    ) {
        $this->clientId     = $this->coalesceEnvString($clientId, 'OAUTH_CLIENT_ID', 'client_credentials');
        $this->clientSecret = $this->coalesceEnvString($clientSecret, 'OAUTH_CLIENT_SECRET', 'hunter2');
        $this->tokenUrl     = $this->coalesceEnvString($tokenUrl, 'OAUTH_TOKEN_URL', 'http://127.0.0.1:8080');
        $this->scope        = $this->coalesceEnvString($scope, 'OAUTH_SCOPE', 'ALL:ALL');
    }

    /**
     * @param string|null $override
     * @param string $envVar
     * @param string|null $default
     * @return string
     * @throws InvalidConfigException
     */
    private function coalesceEnvString(?string $override, string $envVar, ?string $default = null): string
    {
        if ($override !== null) {
            return $override;
        }
        $var = getenv($envVar);
        if ($var === false && $default === null) {
            throw new InvalidConfigException($envVar);
        }
        if ($var !== false) {
            return $var;
        }
        return $default;
    }

    /**
     * @return UriInterface
     */
    public function getTokenURL(): UriInterface
    {
        return new Uri($this->tokenUrl);
    }

    /**
     * @return array
     */
    public function requestBody(): array
    {
        return [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => $this->scope,
        ];
    }
}