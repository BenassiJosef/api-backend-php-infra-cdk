<?php

namespace StampedeTests\app\src\Package\Exceptions;

use App\Package\Exceptions\BaseException;
use PHPUnit\Framework\TestCase;

class BaseExceptionTest extends TestCase
{
    public function testExceptionType()
    {
        $exception = new NotFoundException();
        self::assertEquals('NotFound', $exception->getTitle());
    }

}
