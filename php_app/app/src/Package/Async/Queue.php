<?php


namespace App\Package\Async;

use Aws\Sqs\SqsClient;
use Generator;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

class Queue implements MessageSender, BulkMessageSender, MessageReceiver
{
    /**
     * @var QueueConfig $config
     */
    private $config;

    /**
     * @var SqsClient $client
     */
    private $client;

    /**
     * @var CancellationChecker $cancellationChecker
     */
    private $cancellationChecker;

    /**
     * @var Pinger $pinger
     */
    private $pinger;

    /**
     * Queue constructor.
     * @param $config
     */
    public function __construct(QueueConfig $config)
    {
        $this->config              = $config;
        $this->client              = $config->client();
        $this->cancellationChecker = new StubCancellationChecker();
        $this->pinger              = new NopPinger();
    }

    /**
     * @param CancellationChecker $cancellationChecker
     */
    public function setCancellationChecker(CancellationChecker $cancellationChecker): void
    {
        $this->cancellationChecker = $cancellationChecker;
    }

    /**
     * @param Pinger $pinger
     * @return Queue
     */
    public function setPinger(Pinger $pinger): Queue
    {
        $this->pinger = $pinger;
        return $this;
    }

    /**
     * @param array | JsonSerializable $body
     * @return Message
     */
    public function sendMessageJson($body): Message
    {
        return $this->sendMessage(json_encode($body));
    }

    /**
     * @param string $body
     * @return Message
     */
    public function sendMessage(string $body): Message
    {
        $result = $this->client->sendMessage(
            [
                'QueueUrl'    => $this->config->getUrl(),
                'MessageBody' => $body,
            ]
        );
        $this->sentMessage();
        return new Message(
            $this->config,
            [
                'MessageId' => $result['MessageId'],
                'Body'      => $body,
            ]
        );
    }

    private function sentMessage()
    {
        if (extension_loaded('newrelic')) {
            newrelic_record_custom_event(
                'SQSMessageSent',
                [
                    'url' => $this->config->getUrl(),
                ]
            );
        }
    }

    /**
     * @param array[] | JsonSerializable[] $messages
     * @return Message[] | array
     */
    public function sendMessagesJson(array $messages): array
    {
        $stringMessages = [];
        foreach ($messages as $message) {
            $stringMessages[] = json_encode($message);
        }
        return $this->sendMessages($stringMessages);
    }

    /**
     * @param string[] $bodies
     * @return Message[]
     */
    public function sendMessages(array $bodies): array
    {
        $messages = [];
        $chunks = array_chunk($bodies, 10);
        foreach ($chunks as $chunk) {
            $messages = array_merge($messages, $this->sendBatch($chunk));
        }
        return $messages;
    }

    /**
     * @param string[] $bodies
     * @return Message[]
     */
    private function sendBatch(array $bodies): array
    {
        $entries = $this->prepareEntries($bodies);
        $results = $this->client->sendMessageBatch(
            [
                'QueueUrl' => $this->config->getUrl(),
                'Entries'  => array_values($entries),
            ]
        );

        if (!$results->hasKey('Successful')) {
            return [];
        }
        $successful = $results->get('Successful');
        /** @var Message[] $messages */
        $messages = [];
        foreach ($successful as $key => $result) {
            $id         = $result['Id'];
            $messages[] = new Message(
                $this->config,
                [
                    'MessageId' => $result['MessageId'],
                    'Body'      => $entries[$id],
                ]
            );
            $this->sentMessage();
        }
        return $messages;
    }

    private function prepareEntries(array $bodies): array
    {
        $entries = [];
        foreach ($bodies as $body) {
            $id           = Uuid::uuid1()->toString();
            $entries[$id] = [
                'Id'          => $id,
                'MessageBody' => $body,
            ];
        }
        return $entries;
    }

    /**
     * @return Generator | Message[]
     */
    public function messages()
    {
        while (!$this->cancellationChecker->cancelled()) {
            $this->pinger->ping();
            foreach ($this->receiveMessages() as $message) {
                yield $message;
            }
        }
    }

    /**
     * @return array
     */
    private function receiveMessages(): array
    {
        $result   = $this->client->receiveMessage(
            [
                'AttributeNames'        => ['SentTimestamp'],
                'MaxNumberOfMessages'   => 1,
                'MessageAttributeNames' => ['All'],
                'QueueUrl'              => $this->config->getUrl(),
                'WaitTimeSeconds'       => $this->config->getWaitTimeout(),
            ]
        );
        $messages = $result->get('Messages');
        if ($messages === null || count($messages) === 0) {
            return [];
        }
        $output = [];
        foreach ($messages as $rawMessage) {
            $output[] = new Message($this->config, $rawMessage);
        }
        return $output;
    }

}