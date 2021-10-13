<?php

namespace App\Package\Clients\Delorean;

use JsonSerializable;

class Job implements JsonSerializable
{
    /**
     * @var string $service
     */
    private $service;

    /**
     * @var string $path
     */
    private $path;

    /**
     * @var string $method
     */
    private $method;

    /**
     * @var string | null $body
     */
    private $body;

    /**
     * Job constructor.
     * @param string $service
     * @param string $path
     * @param string $method
     * @param string | null $body
     */
    public function __construct(
        string $service,
        string $path,
        string $method,
        string $body = null
    ) {
        $this->service = $service;
        $this->path    = $path;
        $this->method  = $method;
        $this->body    = $body;
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
        return [
            'service' => $this->service,
            'path'    => $this->path,
            'method'  => $this->method,
            'body'    => $this->body,
        ];
    }
}