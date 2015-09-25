<?php
namespace Skewd\Server\Application;

use Exception;
use Psr\Log\LoggerInterface;
use Skewd\Common\Messaging\Node;
use Skewd\Server\Process\Process;
use SplObjectStorage;

/**
 * An application with functionality composed of modules.
 */
final class ModularApplication implements Application
{
    /**
     * Create a modular application.
     *
     * @param Node            $node   The messaging node used for communication.
     * @param LoggerInterface $logger The logger to use for application output.
     *
     * @return ModularApplication
     */
    public static function create(Node $node, LoggerInterface $logger)
    {
        return new self($node, $logger);
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
            $this->node->connect();
        } catch (Exception $e) {
            $this->logger->critical(
                'Node failed to establish an AMQP connection: {message}',
                [
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            return false;
        }

        $this->logger->info(
            'Node initialized, node ID is {id}',
            ['id' => $this->node->id()]
        );

        foreach ($this->modules as $module) {
            try {
                $module->initialize($this->node);
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

        try {
            $this->node->disconnect();
        } catch (Exception $e) {
            $this->logger->warning(
                'Node failed to disconnect from AMQP gracefully: {message}',
                [
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            $result = false;
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
        try {
            if ($this->node->wait(self::WAIT_TIMEOUT)) {
                return true;
            }
        } catch (Exception $e) {
            $this->logger->critical(
                'Node failed while waiting for activity: {message}',
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
     * Please note that this code is not part of the public API. It may be
     * changed or removed at any time without notice.
     *
     * @access private
     *
     * This constructor is public so that it may be used by auto-wiring
     * dependency injection containers. If you are explicitly constructing an
     * instance please use one of the static factory methods listed below.
     *
     * @see ModularApplication::create()
     *
     * @param Node            $node   The node.
     * @param LoggerInterface $logger The logger to use for application output.
     */
    public function __construct(Node $node, LoggerInterface $logger)
    {
        $this->node = $node;
        $this->logger = $logger;

        $this->clear();
    }

    const WAIT_TIMEOUT = 0.1;

    private $node;
    private $logger;
    private $modules;
}
