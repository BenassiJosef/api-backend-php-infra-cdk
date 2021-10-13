<?php

namespace StampedeTests\app\src\Package\PrettyIds;

use App\Package\PrettyIds\HumanReadable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use StampedeTests\Helpers\DoctrineHelpers;

class HumanReadableTest extends TestCase
{
    public function testPrettify()
    {
        $uuidStr    = "db467496-de73-4749-a228-1d229fe12423";
        $uuid       = Uuid::fromString($uuidStr);
        $prettifier = new HumanReadable();
        $pretty     = "CZC6M-K3AQD-HYAK0-8BOPJ-TV50J";
        self::assertEquals($pretty, $prettifier->prettify($uuid));
        self::assertEquals($uuidStr, $prettifier->unpretty($pretty)->toString());
    }

    public function testInvalidPrettyIds()
    {
        $prettifier = new HumanReadable();
        $uuid = Uuid::fromString('00315ee3-be74-46e4-9bc1-4fa4ec489bce');
        $pretty =  $prettifier->prettify($uuid);
        $ugly = $prettifier->unpretty($pretty);
        self::assertEquals($pretty, $prettifier->prettify($uuid));
        self::assertEquals($ugly, $prettifier->unpretty($pretty)->toString());
    }
}
