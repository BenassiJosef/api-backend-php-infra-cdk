<?php

namespace StampedeTests\app\src\Package\PrettyIds;

use App\Package\PrettyIds\HumanReadable;
use App\Package\PrettyIds\URL;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class URLTest extends TestCase
{

    public function testPrettify()
    {
        $uuidStr    = "db467496-de73-4749-a228-1d229fe12423";
        $uuid       = Uuid::fromString($uuidStr);
        $prettifier = new URL();
        $pretty     = "6flW6XRixhWVk7Kz3sBUNn";
        self::assertEquals($pretty, $prettifier->prettify($uuid));
        self::assertEquals($uuidStr, $prettifier->unpretty($pretty)->toString());
    }
}
