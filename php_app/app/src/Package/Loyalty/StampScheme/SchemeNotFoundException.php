<?php


namespace App\Package\Loyalty\StampScheme;

use Exception;
use Ramsey\Uuid\UuidInterface;

class SchemeNotFoundException extends Exception
{

    /**
     * SchemeNotFoundException constructor.
     * @param UuidInterface $schemeId
     */
    public function __construct(UuidInterface $schemeId)
    {
        $schemeIdStr = $schemeId->toString();
        parent::__construct("Could not find scheme ${schemeIdStr}");
    }
}
