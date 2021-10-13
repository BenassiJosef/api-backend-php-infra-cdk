<?php


namespace App\Package\Clients\Delorean;

use App\Package\Config\ConfigLoader;
use App\Package\Config\Exceptions\FailedToLoadEnvironmentVarException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Class Config
 * @package App\Package\Clients\delorean
 */
class DeloreanConfig
{
    /**
     * @var string $baseUrl
     */
    private $baseUrl;

    /**
     * Config constructor.
     * @param string|null $baseUrl
     * @throws FailedToLoadEnvironmentVarException
     */
    public function __construct(
        ?string $baseUrl = null
    ) {
        $this->baseUrl = ConfigLoader::coalesceEnvString(
            'DELOREAN_BASE_URL',
            $baseUrl,
            'http://delorean:8080'
        );
    }

    /**
     * @param string $path
     * @return UriInterface
     */
    public function baseURLWithPath(string $path) : UriInterface
    {
        return (new Uri($this->baseUrl))->withPath($path);
    }
}
