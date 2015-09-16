<?php
namespace Skewd\Process;

/**
 * Defines the action to be taken by a ticking process.
 */
interface TickingProcessAction
{
    /**
     * Initialize the process.
     *
     * @param Process $process The process performing this action.
     *
     * @return boolean True if the process initialized successfully; otherwise, false.
     */
    public function initialize(Process $process);

    /**
     * Shut down the process.
     *
     * This method is invoked
     *
     * @return boolean True if the process shut down successfully; otherwise, false.
     */
    public function shutdown();

    /**
     * Perform the process' work.
     *
     * This method is invoked repeatedly while the ticking process is running.
     *
     * @return boolean True if the process ticked successfully; otherwise, false.
     */
    public function tick();
}
