<?php
namespace Skewd\Server;

use LogicException;

/**
 * Interface for a server.
 */
interface Server
{
    /**
     * Start the server and loop until it is stopped.
     *
     * @throws LogicException if the server has already been started.
     */
    public function start();

    /**
     * Instruct the server to stop.
     *
     * The server will stop on the next tick.
     */
    public function stop();

    /**
     * Instruct the server to restart.
     *
     * The server will shutdown and re-initialize on the next tick.
     */
    public function restart();
}
