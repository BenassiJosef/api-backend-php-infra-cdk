<?php


namespace App\Package\Async;

use Exception;
use JsonSerializable;

class Message implements JsonSerializable
{
    /**
     * @var string $id
     */
    private $id;

    /**
     * @var string | null $receiptHandle
     */
    private $receiptHandle;

    /**
     * @var string $body
     */
    private $body;

    /**
     * @var QueueConfig $config
     */
    private $config;

    /**
     * Message constructor.
     * @param QueueConfig $config
     * @param array $raw
     */
    public function __construct(
        QueueConfig $config,
        array $raw
    ) {
        $this->config        = $config;
        $this->id            = $raw['MessageId'];
        $this->receiptHandle = $raw['ReceiptHandle'] ?? null;
        $this->body          = $raw['Body'] ?? '';
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getBodyJson(): array
    {
        return json_decode($this->body, true);
    }

    /**
     * @throws Exception
     */
    public function delete()
    {
        if ($this->receiptHandle === null){
            throw new Exception('Cannot delete message that you have not received');
        }
        $this
            ->config
            ->client()
            ->deleteMessage(
                [
                    'QueueUrl'      => $this->config->getUrl(),
                    'ReceiptHandle' => $this->receiptHandle,
                ]
            );
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'MessageId' => $this->id,
            'Body'      => $this->body,
        ];
    }
}