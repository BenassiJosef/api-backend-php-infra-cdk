<?php

namespace App\Package\Auth\Scopes;

use App\Package\Auth\RequestValidator;
use App\Package\Auth\Scopes\Exceptions\InvalidFormatException;
use App\Package\Auth\Scopes\Exceptions\InvalidNamespaceException;
use App\Package\Auth\Scopes\Exceptions\InvalidServiceException;
use App\Package\Auth\Scopes\Exceptions\InvalidTypeException;
use Ergebnis\Http\Method;
use JsonSerializable;
use Slim\Http\Request;
use Slim\Route;

/**
 * Class Scope
 * @package App\Package\Auth
 */
class Scope implements JsonSerializable, RequestValidator
{
    /**
     * @param array $namespace
     * @throws InvalidNamespaceException
     */
    public static function validateNamespace(array $namespace): void
    {
        foreach ($namespace as $component) {
            if (!is_string($component)) {
                throw new InvalidNamespaceException($namespace);
            }
        }
    }

    /**
     * @param string $type
     * @throws InvalidTypeException
     */
    public static function validateType(string $type): void
    {
        if (!array_key_exists($type, self::$allowedMethodsForScopes)) {
            throw new InvalidTypeException($type);
        }
    }

    /**
     * @param string $serviceName
     * @throws InvalidServiceException
     */
    public static function validateService(string $serviceName): void
    {
        if (!array_key_exists($serviceName, self::$allowedServices)) {
            throw new InvalidServiceException($serviceName);
        }
    }

    /**
     * @param string $scope
     * @return static
     * @throws InvalidFormatException
     * @throws InvalidServiceException
     * @throws InvalidTypeException
     * @throws InvalidNamespaceException
     */
    public static function fromString(string $scope): self
    {
        if ($scope === '') {
            throw new InvalidFormatException($scope);
        }
        $scopeParts = string($scope)
            ->toLower()
            ->explode(':');
        switch (count($scopeParts)) {
            case 1:
                [$type] = $scopeParts;
                return new self($type);
            case 2:
                [$type, $service] = $scopeParts;
                return new self($type, $service);
            default:
                [$type, $service] = array_slice($scopeParts, 0, 2);
                return new self(
                    $type,
                    $service,
                    array_slice($scopeParts, 2)
                );
        }
    }

    /**
     * @return string[]
     */
    public static function allowedTypes(): array
    {
        return array_keys(self::$allowedMethodsForScopes);
    }

    /**
     * @return array
     */
    public static function allowedServices(): array
    {
        return array_keys(self::$allowedServices);
    }

    const TYPE_READ   = 'read';
    const TYPE_WRITE  = 'write';
    const TYPE_ALL    = 'all';
    const TYPE_SYSTEM = 'system';

    const SERVICE_BACKEND           = 'backend';
    const SERVICE_SEGMENT_MARKETING = 'segment_marketing';
    const SERVICE_ALL               = 'all';

    /**
     * @var bool[][] $allowedMethodsForScopes
     */
    private static $allowedMethodsForScopes = [
        self::TYPE_READ   => [
            Method::GET     => true,
            Method::CONNECT => true,
            Method::HEAD    => true,
            Method::OPTIONS => true,
            Method::TRACE   => true,
        ],
        self::TYPE_WRITE  => [
            Method::POST   => true,
            Method::PUT    => true,
            Method::PATCH  => true,
            Method::DELETE => true,
        ],
        self::TYPE_ALL    => [
            Method::GET     => true,
            Method::CONNECT => true,
            Method::HEAD    => true,
            Method::OPTIONS => true,
            Method::TRACE   => true,
            Method::POST    => true,
            Method::PUT     => true,
            Method::PATCH   => true,
            Method::DELETE  => true,
        ],
        self::TYPE_SYSTEM => [
            Method::GET     => true,
            Method::CONNECT => true,
            Method::HEAD    => true,
            Method::OPTIONS => true,
            Method::TRACE   => true,
            Method::POST    => true,
            Method::PUT     => true,
            Method::PATCH   => true,
            Method::DELETE  => true,
        ],
    ];

    /**
     * @var bool[] $allowedServices
     */
    private static $allowedServices = [
        self::SERVICE_ALL               => true,
        self::SERVICE_BACKEND           => true,
        self::SERVICE_SEGMENT_MARKETING => true,
    ];

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var string $service
     */
    private $service;

    /**
     * @var string[] $namespace
     */
    private $namespace;

    /**
     * Scope constructor.
     * @param string $type
     * @param string $service
     * @param string[] $namespace
     * @throws InvalidNamespaceException
     * @throws InvalidServiceException
     * @throws InvalidTypeException
     */
    public function __construct(
        string $type = self::TYPE_ALL,
        string $service = self::SERVICE_ALL,
        array $namespace = []
    ) {
        self::validateType($type);
        self::validateService($service);
        self::validateNamespace($namespace);
        $this->type      = $type;
        $this->service   = $service;
        $this->namespace = $namespace;
    }

    /**
     * @param string $service
     * @param Request $request
     * @return bool
     */
    public function canRequest(string $service, Request $request): bool
    {
        if ($this->service !== $service
            && $this->service !== self::SERVICE_ALL
        ) {
            return false;
        }
        if (!$this->isMethodAllowed($request->getMethod())) {
            return false;
        }
        if (!$this->isRequestPathAllowed($request)) {
            return false;
        }
        return true;
    }

    /**
     * @param string $method
     * @return bool
     */
    private function isMethodAllowed(string $method): bool
    {
        return array_key_exists($method, self::$allowedMethodsForScopes[$this->type]);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isRequestPathAllowed(Request $request): bool
    {
        foreach ($this->pathsFromRequest($request) as $path) {
            if ($this->isPathAllowed($path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Request $request
     * @return string[]
     */
    private function pathsFromRequest(Request $request): array
    {
        $paths = [
            $request->getUri()->getPath(),
        ];
        /** @var Route $route */
        $route = $request->getAttribute('route');
        if ($route !== null) {
            $paths[] = $route->getPattern();
        }
        return from($paths)
            ->select(
                function (string $path): string {
                    return string($path)->toLower();
                }
            )
            ->toArray();
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isPathAllowed(string $path): bool
    {
        if (count($this->namespace) === 0) {
            return true;
        }
        return string($path)
            ->toLower()
            ->startsWith($this->namespaceAsPath());
    }

    /**
     * @return string
     */
    private function namespaceAsPath(): string
    {
        $path = from($this->namespace)
            ->select(
                function (string $component): string {
                    return string($component)->toLower();
                }
            )
            ->toString('/');
        return "/${path}";
    }

    /**
     * The __toString method allows a class to decide how it will react when it is converted to a string.
     *
     * @return string
     * @link https://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    public function __toString()
    {
        return implode(
            ':',
            [
                string($this->type)->toUpper(),
                string($this->service)->toUpper()
            ]
        );
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
        return (string)$this;
    }
}