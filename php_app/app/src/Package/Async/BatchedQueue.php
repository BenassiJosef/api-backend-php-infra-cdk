<?php


namespace App\Package\Async;


class BatchedQueue implements MessageSender, Flusher
{
    /**
     * @var string[]
     */
    private $messages = [];
    /**
     * @var BulkMessageSender $bulkSender
     */
    private $bulkSender;

    /**
     * @var QueueConfig $config
     */
    private $config;

    /**
     * BatchedQueue constructor.
     * @param BulkMessageSender $bulkSender
     * @param QueueConfig $config
     */
    public function __construct(BulkMessageSender $bulkSender, QueueConfig $config)
    {
        $this->bulkSender = $bulkSender;
        $this->config     = $config;
    }

    /**
     * @param string $body
     * @return Message
     */
    public function sendMessage(string $body): Message
    {
        $this->messages[] = $body;
        return new Message(
            $this->config,
            [
                'MessageId' => '',
                'Body'      => $body,
            ]
        );
    }

    /**
     * @param $body
     * @return Message
     */
    public function sendMessageJson($body): Message
    {
        $message          = json_encode($body);
        $this->messages[] = $message;
        return new Message(
            $this->config,
            [
                'MessageId' => '',
                'Body'      => $message,
            ]
        );
    }

    /**
     * @throws FlushException
     */
    public function flush(): void
    {
        if (count($this->messages) === 0) {
            return;
        }
        $bodies         = $this->messages;
        $this->messages = [];
        $messages       = $this->bulkSender->sendMessages($bodies);
        if (count($messages) !== count($bodies)) {
            throw new FlushException('some messages were not pushed to the queue');
        }
    }
}
