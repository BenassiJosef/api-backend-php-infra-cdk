<?php

namespace App\Package\Exceptions;

use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Throwable;

/**
 * Class InvalidUUIDException
 * @package App\Package\Exceptions
 */
class InvalidUUIDException extends BaseException
{
    /**
     * InvalidUUIDException constructor.
     * @param string|null $id
     * @param string|null $idName
     * @param Throwable|null $previous
     * @throws Exception
     */
    public function __construct(?string $id, string $idName, Throwable $previous = null)
    {
        parent::__construct(
            $this->message($id, $idName),
            StatusCodes::HTTP_BAD_REQUEST,
            $this->extra($id, $idName),
            $previous
        );
    }

    /**
     * @param string $id
     * @param string|null $idName
     * @return string
     */
    private function message(?string $id, string $idName): string
    {
        if ($id === null) {
            $id = 'null';
        }
        return "(${id}) is not a valid UUID for field (${idName})";
    }

    /**
     * @param string $id
     * @param string|null $idName
     * @return string[]
     */
    private function extra(?string $id, string $idName): array
    {
        if ($id === null) {
            $id = 'null';
        }
        return [
            $idName => $id
        ];
    }
}