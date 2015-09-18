<?php
namespace Skewd\Server\Process;

use LogicException;

/**
 * A process is the main entry point for an executable.
 */
interface Process
{
    /**
     * Start the process.
     *
     * Blocks until stop() is called.
     *
     * @throws LogicException if the process has already been started.
     */
    public function start();

    /**
     * Stop the process.
     *
     * Instructs the running process to shutdown gracefully and exit.
     */
    public function stop();

    /**
     * Restart the process.
     *
     * Instructs the running process to shutdown gracefully and restart.
     */
    public function restart();
}
