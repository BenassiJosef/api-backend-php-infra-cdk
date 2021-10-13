<?php

namespace App\Package\Response;

use Psr\Http\Message\UriInterface;

interface Response
{
    /**
     * @return UriInterface|null
     */
    public function getType(): ?UriInterface;

    /**
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * @return string|null
     */
    public function getDetail(): ?string;

    /**
     * @return UriInterface|null
     */
    public function getInstance(): ?UriInterface;

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize();
}