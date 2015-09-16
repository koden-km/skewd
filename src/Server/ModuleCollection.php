<?php
namespace Skewd\Server;

use Exception;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

/**
 * A collection of modules used by a modular server.
 */
final class ModuleCollection
{
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->clear();
    }

    /**
     * Add a module to the collection.
     *
     * @param Module $module
     */
    public function add(Module $module)
    {
        $this->modules->attach($module);
    }

    /**
     * Remove a module from the collection.
     *
     * @param Module $module
     */
    public function remove(Module $module)
    {
        $this->modules->detach($module);
    }

    /**
     * Check if a module is present in this collection.
     *
     * @param Module $module
     *
     * @return boolean
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
        $this->modules = new SplObjectStorage;
    }

    /**
     * Initialize all modules in the collection.
     *
     * @param Server $server The server under which the module is running.
     *
     * @return boolean True if all modules initialized successfully; otherwise, false.
     */
    public function initialize(Server $server)
    {
        foreach ($this->modules as $module) {
            try {
                $module->initialize($server);
            } catch (Exception $e) {
                $this->logger->critical(
                    'Failed to initialize server module "{name}": {message}',
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
     * Shut down all modules in the collection.
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
                    'Failed to shut down server module "{name}": {message}',
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
     * Perform all module's actions.
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
                    'Failure in server module "{name}": {message}',
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
