<?php


namespace App\Package\Response;

use JsonSerializable;
use Psr\Http\Message\UriInterface;
use Slim\Http\Response as SlimResponse;

class BodyResponse implements Response, JsonSerializable, Responder
{
    /**
     * @param int $status
     * @param string $detail
     * @param JsonSerializable | array $body
     * @return static
     */
    public static function fromStatusAndBody(
        int $status,
        string $detail,
        $body
    ): self {
        return new self(
            ProblemResponse::fromStatus($status, $detail),
            $body
        );
    }

    /**
     * @var Response $baseResponse
     */
    private $baseResponse;

    /**
     * @var JsonSerializable | array
     */
    private $body;

    /**
     * BodyResponse constructor.
     * @param Response $baseResponse
     * @param array|JsonSerializable $body
     */
    public function __construct(
        Response $baseResponse,
        $body
    ) {
        $this->baseResponse = $baseResponse;
        $this->body         = $body;
    }

    /**
     * @inheritDoc
     */
    public function getType(): ?UriInterface
    {
        return $this->baseResponse->getType();
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return $this->baseResponse->getTitle();
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?int
    {
        return $this->baseResponse->getStatus();
    }

    /**
     * @inheritDoc
     */
    public function getDetail(): ?string
    {
        return $this->baseResponse->getDetail();
    }

    /**
     * @inheritDoc
     */
    public function getInstance(): ?UriInterface
    {
        return $this->baseResponse->getInstance();
    }

    /**
     * @return array|JsonSerializable
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param SlimResponse $response
     * @return SlimResponse
     */
    public function respond(SlimResponse $response): SlimResponse
    {
        return $response
            ->withStatus($this->getStatus())
            ->withJson($this);
    }


    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return array_merge(
            $this->baseResponse->jsonSerialize(),
            [
                'body' => $this->body,
            ]
        );
    }
}