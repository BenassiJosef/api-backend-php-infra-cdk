<?php

namespace App\Package\Clients\Delorean;

use App\Package\Clients\Delorean\Exceptions\FailedToScheduleJobException;
use App\Package\Clients\InternalOAuth\Middleware;
use App\Package\Clients\InternalOAuth\TokenSource;
use DateTime;
use DateTimeImmutable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Throwable;

/**
 * Class Client
 * @package App\Package\Clients\delorean
 */
class DeloreanClient
{
    /**
     * @param DeloreanConfig $config
     * @param TokenSource $source
     * @return static
     */
    public static function make(DeloreanConfig $config, TokenSource $source): self
    {
        $stack = HandlerStack::create(new CurlHandler());
        $stack->push((new Middleware($source))->middleware());
        return new self(
            $config,
            new Client(['handler' => $stack])
        );
    }

    /**
     * @var DeloreanConfig $config
     */
    private $config;

    /**
     * @var Client $guzzleClient
     */
    private $guzzleClient;

    /**
     * Client constructor.
     * @param DeloreanConfig $config
     * @param Client $guzzleClient
     */
    public function __construct(
        DeloreanConfig $config,
        Client $guzzleClient
    ) {
        $this->config       = $config;
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param string $namespace
     * @param DateTimeImmutable $for
     * @param Job $job
     * @return Response
     * @throws FailedToScheduleJobException
     * @throws Exception
     */
    public function scheduleHTTP(
        string $namespace,
        DateTimeImmutable $for,
        Job $job
    ): Response {
        $request = new Request(
            $namespace,
            $for,
            $job
        );
        try {
            $resp = $this
                ->guzzleClient
                ->post(
                    $this->config->baseURLWithPath('/schedule/http'),
                    [
                        RequestOptions::JSON => $request,
                    ]
                );
        } catch (Throwable $exception) {
            throw new FailedToScheduleJobException($request, $exception);
        }
        return Response::fromArray(
            json_decode(
                $resp->getBody()->getContents(),
                true
            )
        );
    }
}