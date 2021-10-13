<?php

namespace App\Package\Segments\Exceptions;

use App\Package\Exceptions\BaseException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Ramsey\Uuid\UuidInterface;

/**
 * Class PersistentSegmentNotFoundException
 * @package App\Package\Segments\Exceptions
 */
class PersistentSegmentNotFoundException extends BaseException
{
    /**
     * PersistentSegmentNotFoundException constructor.
     * @param UuidInterface $id
     * @param UuidInterface|null $version
     */
    public function __construct(UuidInterface $id, ?UuidInterface $version = null)
    {
        $idString      = $id->toString();
        $versionString = null;
        if ($version !== null) {
            $versionString = $version->toString();
        }
        $message = "Could not find segment with id (${idString})";
        if ($versionString !== null) {
            $message .= " and version (${versionString})";
        }
        parent::__construct(
            $message,
            StatusCodes::HTTP_NOT_FOUND
        );
    }
}