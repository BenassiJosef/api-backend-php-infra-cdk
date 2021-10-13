<?php


namespace App\Package\Response;


use GuzzleHttp\Psr7\Uri;
use JsonSerializable;
use Lukasoppermann\Httpstatus\Httpstatus;
use Psr\Http\Message\UriInterface;

class ProblemResponse implements JsonSerializable, Response
{

    public static function fromStatus(
        int $statusCode,
        string $detail = null
    ): self {
        return new self(
            new Uri('http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html'),
            (new Httpstatus())->getReasonPhrase($statusCode),
            $statusCode,
            $detail
        );
    }

    /**
     * @var UriInterface | null $type
     */
    private $type;

    /**
     * @var string | null $title
     */
    private $title;

    /**
     * @var int | null $status
     */
    private $status;

    /**
     * @var string | null $detail
     */
    private $detail;

    /**
     * @var UriInterface | null $instance
     */
    private $instance;

    /**
     * ProblemResponse constructor.
     * @param UriInterface|null $type
     * @param string|null $title
     * @param int|null $status
     * @param string|null $detail
     * @param UriInterface|null $instance
     */
    public function __construct(
        ?UriInterface $type,
        ?string $title = 'OK',
        ?int $status = 200,
        ?string $detail = null,
        ?UriInterface $instance = null
    ) {
        $this->type     = $type;
        $this->title    = $title;
        $this->status   = $status;
        $this->detail   = $detail;
        $this->instance = $instance;
    }

    /**
     * @return UriInterface|null
     */
    public function getType(): ?UriInterface
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    /**
     * @return UriInterface|null
     */
    public function getInstance(): ?UriInterface
    {
        return $this->instance;
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
        $body = [];
        if ($this->type !== null) {
            $body['type'] = $this->type->__toString();
        }
        if ($this->title !== null) {
            $body['title'] = $this->title;
        }
        if ($this->status !== null) {
            $body['status'] = $this->status;
        }
        if ($this->detail !== null) {
            $body['detail'] = $this->detail;
        }
        if ($this->instance !== null) {
            $body['instance'] = $this->instance->__toString();
        }
        return $body;
    }
}