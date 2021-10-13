<?php


namespace App\Package\Auth\Scopes;

use App\Package\Auth\RequestValidator;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Slim\Http\Request;

class Scopes implements JsonSerializable, RequestValidator
{
    /**
     * @param string | null $scopes
     * @return static
     */
    public static function fromString(?string $scopes): self
    {
        if ($scopes === null || $scopes === '') {
            return new self([]);
        }
        return new self(
            from(string($scopes)->explode(' '))
                ->select(
                    function (string $scope): Scope {
                        return Scope::fromString($scope);
                    }
                )
                ->toArray()
        );
    }

    /**
     * @var Scope[] $scopes
     */
    private $scopes;

    /**
     * Scopes constructor.
     * @param Scope[] $scopes
     */
    public function __construct(array $scopes)
    {
        $this->scopes = from($scopes)
            ->select(
                function (Scope $scope): Scope {
                    return $scope;
                },
                function (Scope $scope): string {
                    return (string)$scope;
                }
            )
            ->toArray();
    }

    /**
     * @param string $service
     * @param Request $request
     * @return bool
     */
    public function canRequest(string $service, Request $request): bool
    {
        if (count($this->scopes) === 0) {
            return true;
        }
        foreach ($this->scopes as $scope) {
            if ($scope->canRequest($service, $request)) {
                return true;
            }
        }
        return false;
    }

    /**
     * The __toString method allows a class to decide how it will react when it is converted to a string.
     *
     * @return string
     * @link https://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    public function __toString()
    {
        return implode(' ', $this->scopes);
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