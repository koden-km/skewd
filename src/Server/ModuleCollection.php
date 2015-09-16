<?php
namespace Skewd\Server;


/**
 * A collection of modules used by a modular server.
 */
interface ModuleCollection
{
    /**
     * Add a module to the collection.
     *
     * @param Module $module The module to add.
     */
    public function add(Module $module);

    /**
     * Remove a module from the collection.
     *
     * @param Module $module The module to remove.
     */
    public function remove(Module $module);

    /**
     * Check if a module is present in this collection.
     *
     * @param Module $module The module to check.
     *
     * @return boolean True if the module is in the collection; otherwise, false.
     */
    public function has(Module $module);

    /**
     * Remove all modules from the collection.
     */
    public function clear();

    /**
     * Initialize all modules in the collection.
     *
     * @param Server $server The server under which the modules are running.
     *
     * @return boolean True if all modules were initialized successfully; otherwise, false.
     */
    public function initialize(Server $server);

    /**
     * Shut down all modules in the collection.
     *
     * @return boolean True if all modules shut down successfully; otherwise, false.
     */
    public function shutdown();

    /**
     * Perform all module's actions.
     *
     * @return boolean True if all modules ticked successfully; otherwise, false.
     */
    public function tick();
}
