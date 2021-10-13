<?php

namespace App\Package\Auth\ExternalServices;

use App\Package\Auth\ExternalServices\Exceptions\InvalidParameterException;
use App\Package\Auth\ExternalServices\Exceptions\MissingKeyException;
use JsonSerializable;
use Slim\Http\Request;
use stdClass;

/**
 * Class AccessCheckRequest
 * @package App\Package\Auth\ExternalServices
 */
class AccessCheckRequest implements JsonSerializable
{
    /**
     * @var string[] $requiredKeys
     */
    private static $requiredKeys = [
        'service',
        'method',
        'path'
    ];

    /**
     * @param Request $request
     * @return static
     * @throws InvalidParameterException
     * @throws MissingKeyException
     */
    public static function fromRequest(Request $request): self
    {
        return self::fromArray(
            $request->getParsedBody()
        );
    }

    /**
     * @param array $data
     * @return static
     * @throws InvalidParameterException
     * @throws MissingKeyException
     */
    public static function fromArray(array $data): self
    {
        self::validateKeys($data);
        return new self(
            $data['service'],
            $data['method'],
            $data['path'],
            $data['pattern'] ?? null,
            $data['parameters'] ?? []
        );
    }

    /**
     * @param array $data
     * @return void
     * @throws MissingKeyException
     */
    public static function validateKeys(array $data): void
    {
        foreach (self::$requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new MissingKeyException($key, self::$requiredKeys);
            }
        }
    }

    /**
     * @param array $parameters
     * @throws InvalidParameterException
     */
    public static function validateParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            if (!is_string($value)) {
                throw new InvalidParameterException($key, $value);
            }
        }
    }

    /**
     * @var string $service
     */
    private $service;

    /**
     * @var string $method
     */
    private $method;

    /**
     * @var string $path
     */
    private $path;

    /**
     * @var string | null $pattern
     */
    private $pattern;

    /**
     * @var string[] $parameters
     */
    private $parameters;

    /**
     * AccessCheckRequest constructor.
     * @param string $service
     * @param string $method
     * @param string $path
     * @param string | null $pattern
     * @param string[] $parameters
     * @throws InvalidParameterException
     */
    public function __construct(
        string $service,
        string $method,
        string $path,
        ?string $pattern = null,
        array $parameters = []
    ) {
        self::validateParameters($parameters);
        $this->service    = $service;
        $this->method     = $method;
        $this->path       = $path;
        $this->pattern    = $pattern;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string|null
     */
    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    /**
     * @return string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return stdClass|string[]
     */
    private function jsonParameters()
    {
        if (count($this->parameters) === 0) {
            return new stdClass();
        }
        return $this->parameters;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return [
            'service'    => $this->service,
            'method'     => $this->method,
            'path'       => $this->path,
            'pattern'    => $this->pattern,
            'parameters' => $this->jsonParameters(),
        ];
    }
}