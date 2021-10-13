<?php


namespace App\Package\PrettyIds;


use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

interface IDPrettyfier
{
    /**
     * @param UuidInterface $uuid
     * @return string
     */
    public function prettify(UuidInterface $uuid): string;

    /**
     * @param string $id
     * @return UuidInterface
     */
    public function unpretty(string $id): UuidInterface;
}