<?php
namespace Skewd\Server\Application;

use ErrorException;
use Exception;
use LogicException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Log\LoggerInterface;
use Skewd\Common\Amqp\ConnectionFactory;
use Skewd\Server\Process\Process;
use SplObjectStorage;

/**
 * A modular application centered around a single AMQP connection.
 */
final class ModularApplication implements Application
{
    /**
     * @param LoggerInterface $logger The logger to use for application output.
     */
    public function __construct(
        ConnectionFactory $connectionFactory,
        LoggerInterface $logger
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->logger = $logger;
        $this->clear();
    }

    /**
     * Add a module to the collection.
     *
     * @param Module $module The module to add.
     */
    public function add(Module $module)
    {
        $this->modules->attach($module);
    }

    /**
     * Remove a module from the collection.
     *
     * @param Module $module The module to remove.
     */
    public function remove(Module $module)
    {
        $this->modules->detach($module);
    }

    /**
     * Check if a module has been added.
     *
     * @param Module $module The module to check.
     *
     * @return boolean True if the module has been added; otherwise, false.
     */
    public function has(Module $module)
    {
        return $this->modules->contains($module);
    }

    /**
     * Remove all modules.
     */
    public function clear()
    {
        $this->modules = new SplObjectStorage();
    }

    /**
     * Initialize the application.
     *
     * @param Process $process The process that the application is running on.
     *
     * @return boolean True if the process initialized successfully; otherwise, false.
     */
    public function initialize(Process $process)
    {
        try {
            $this->connection = $this->connectionFactory->create();
        } catch (Exception $e) {
            $this->logger->critical(
                'Failed to establish AMQP connection: {message}',
                [
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            return false;
        }

        foreach ($this->modules as $module) {
            try {
                $channel = $this->connection->channel();
                $module->initialize($this, $channel);
                $this->modules[$module] = $channel;
            } catch (Exception $e) {
                $this->logger->critical(
                    'Failed to initialize module "{name}": {message}',
                    [
                        'name' => $module->name(),
                        'message' => $e->getMessage(),
                        'exception' => $e,
                    ]
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Shut down the application.
     *
     * @return boolean True if all modules shut down successfully; otherwise, false.
     */
    public function shutdown()
    {
        $result = true;

        foreach ($this->modules as $module) {
            try {
                $module->shutdown();
            } catch (Exception $e) {
                $this->logger->warning(
                    'Failed to shut down module "{name}": {message}',
                    [
                        'name' => $module->name(),
                        'message' => $e->getMessage(),
                        'exception' => $e,
                    ]
                );

                $result = false;
            }
        }

        if ($this->connection) {
            try {
                if ($this->connection->isConnected()) {
                    $this->connection->close();
                }
            } catch (Exception $e) {
                $this->logger->warning(
                    'Failed to close AMQP connection gracefully: {message}',
                    [
                        'message' => $e->getMessage(),
                        'exception' => $e,
                    ]
                );

                $result = false;
            } finally {
                $this->connection = null;
                $this->channel = null;
            }
        }

        return $result;
    }

    /**
     * Perform each module's action.
     *
     * @return boolean True if all modules ticked successfully; otherwise, false.
     */
    public function tick()
    {
        if (!$this->connection) {
            throw new LogicException('Application has not been initialized.');
        }

        try {
            if ($this->wait()) {
                return true;
            }
        } catch (Exception $e) {
            $this->logger->critical(
                'Failure while waiting for AMQP connection: {message}',
                [
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            return false;
        }

        foreach ($this->modules as $module) {
            try {
                $module->tick();
            } catch (Exception $e) {
                $this->logger->critical(
                    'Failure in module "{name}": {message}',
                    [
                        'name' => $module->name(),
                        'message' => $e->getMessage(),
                        'exception' => $e,
                    ]
                );

                return false;
            }
        }

        return true;
    }

    /**
     * @return boolean True if the select was interrupted by a system call.
     */
    private function wait()
    {
        try {
            $this->connection->select(0, self::SELECT_TIMEOUT * 1000000);
        } catch (ErrorException $e) {
            if (false === strpos($e->getMessage(), 'Interrupted system call')) {
                throw $e;
            }

            return true;
        }

        foreach ($this->modules as $module) {
            try {
                $this->modules[$module]->wait(
                    null, // allowed methods
                    true, // non-blocking
                    self::CHANNEL_WAIT_TIMEOUT // timeout
                );
            } catch (AMQPTimeoutException $e) {
                // ignore ...
            }
        }

        return false;
    }

    const SELECT_TIMEOUT       = 0.1;
    const CHANNEL_WAIT_TIMEOUT = 0.000001;

    private $connectionFactory;
    private $connection;
    private $logger;
    private $modules;
}
