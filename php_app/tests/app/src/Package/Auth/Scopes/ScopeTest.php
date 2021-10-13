<?php

namespace StampedeTests\app\src\Package\Auth\Scopes;

use App\Package\Auth\Scopes\Exceptions\InvalidFormatException;
use App\Package\Auth\Scopes\Exceptions\InvalidNamespaceException;
use App\Package\Auth\Scopes\Exceptions\InvalidServiceException;
use App\Package\Auth\Scopes\Exceptions\InvalidTypeException;
use App\Package\Auth\Scopes\Scope;
use App\Package\Auth\Scopes\Scopes;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    public function testInvalidFormatThrowsException()
    {
        self::expectException(InvalidFormatException::class);
        Scope::fromString('');
    }

    public function testInvalidTypeThrowsException()
    {
        self::expectException(InvalidTypeException::class);
        Scope::fromString('FOO');
    }

    public function testInvalidServiceThrowsException()
    {
        self::expectException(InvalidServiceException::class);
        Scope::fromString('READ:FOO');
    }

    public function testInvalidNamespaceThrowsException()
    {
        self::expectException(InvalidNamespaceException::class);
        $scope = new Scope(Scope::TYPE_ALL, Scope::SERVICE_ALL, [1]);
    }

    public function testScopeFormatIsExpanded()
    {
        $scope = Scope::fromString(Scope::TYPE_ALL);
        self::assertEquals('ALL:ALL', (string)$scope);
        self::assertEquals('ALL:ALL', $scope->jsonSerialize());
    }

    public function testScopesCombinesScopes()
    {
        $scopes = new Scopes(
            [
                Scope::fromString(Scope::TYPE_READ),
                Scope::fromString(Scope::TYPE_WRITE)
            ]
        );
        self::assertEquals('READ:ALL WRITE:ALL', (string)$scopes);
        self::assertEquals('READ:ALL WRITE:ALL', $scopes->jsonSerialize());
    }
}
