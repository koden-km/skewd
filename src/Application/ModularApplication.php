<?php
namespace Skewd\Application;

use Exception;
use Psr\Log\LoggerInterface;
use Skewd\Process\Process;
use SplObjectStorage;

/**
 * A modular application centered around a single AMQP connection.
 */
final class ModularApplication implements Application
{
    /**
     * @param LoggerInterface $logger The logger to use for application output.
     */
    public function __construct(LoggerInterface $logger)
    {
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
        foreach ($this->modules as $module) {
            try {
                $module->initialize($this);
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

        return $result;
    }

    /**
     * Perform each module's action.
     *
     * @return boolean True if all modules ticked successfully; otherwise, false.
     */
    public function tick()
    {
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

    private $logger;
    private $modules;
}
