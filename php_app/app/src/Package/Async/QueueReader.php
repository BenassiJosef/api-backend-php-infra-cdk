<?php


namespace App\Package\Async;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class QueueReader
 * @package CampaignEligibility\Queues
 */
class QueueReader
{
    /**
     * @var Queue $queue
     */
    private $queue;

    /**
     * @var QueueHandler $handler
     */
    private $handler;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string $appName
     */
    private $appName = 'worker-app';

    /**
     * QueueReader constructor.
     * @param string $name
     * @param QueueHandler $handler
     * @param Queue $queue
     * @param LoggerInterface|null $logger
     * @param string|null $appName
     */
    public function __construct(
        string $name,
        QueueHandler $handler,
        Queue $queue,
        LoggerInterface $logger = null,
        ?string $appName = null
    ) {
        $this->name    = $name;
        $this->handler = $handler;
        $this->queue   = $queue;
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
        if ($appName === null) {
            $envAppName = getenv('NEW_RELIC_APP_NAME');
            if ($envAppName !== false) {
                $appName = $envAppName;
            }
        }
        $this->appName = $appName;
    }

    public function run()
    {
        try {
            $this->process();
        } catch (Throwable $throwable) {
            $this->logger->error(
                "got fatal exception processing messages", [
                                                                'message' => $throwable->getMessage(),
                                                                'code'    => $throwable->getCode(),
                                                                'file'    => $throwable->getFile(),
                                                                'line'    => $throwable->getLine(),
                                                                'trace'   => $throwable->getTraceAsString(),
                                                            ]
            );
        }
    }

    /**
     * @throws DriverException
     */
    private function process()
    {
        $this->logger->info("queue reader ({$this->name}) is starting");
        foreach ($this->queue->messages() as $message) {
            $this->start();
            newrelic_add_custom_parameter("worker", $this->name);
            try {
                $this->handler->handleMessage($message);
                $this->success();
            } catch (DeadlockException $driverException) {
            } catch (LockWaitTimeoutException $driverException) {
            } catch (ForeignKeyConstraintViolationException $driverException) {
            } catch (UniqueConstraintViolationException $driverException) {
            } catch (NotNullConstraintViolationException $driverException) {
            } catch (DriverException $driverException) {
                throw $driverException;
            } catch (Throwable $throwable) {
                $this->error($throwable);
            }
        }
        $this->logger->info("queue reader ({$this->name}) has stopped");
    }

    private function start()
    {
        $this->logger->info("queue reader ({$this->name}) received message");
        newrelic_start_transaction($this->appName);
        newrelic_background_job(true);
    }

    private function error(Throwable $exception)
    {
        $this->logger->error(
            "got exception handling message", [
                                                'message' => $exception->getMessage(),
                                                'code'    => $exception->getCode(),
                                                'file'    => $exception->getFile(),
                                                'line'    => $exception->getLine(),
                                                'trace'   => $exception->getTraceAsString(),
                                            ]
        );
        newrelic_notice_error($exception);
        newrelic_end_transaction();
    }

    private function success()
    {
        $this->logger->info("queue reader ({$this->name}) processed message");
        newrelic_end_transaction();
    }
}