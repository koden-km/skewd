<?php
namespace Skewd\Server\Process;

use Exception;
use Icecave\Isolator\IsolatorTrait;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * A process that executes an action repeatedly until stopped.
 */
final class TickingProcess implements Process
{
    /**
     * @param TickingProcessAction $action The action to be performed by the process.
     * @param LoggerInterface      $logger The logger to use for process output.
     */
    public function __construct(
        TickingProcessAction $action,
        LoggerInterface $logger
    ) {
        $this->action = $action;
        $this->logger = $logger;
        $this->status = TickingProcessStatus::STOPPED();
    }

    /**
     * Start the process and loop until it is finished.
     *
     * @throws LogicException if the process has already been started.
     */
    public function start()
    {
        if (TickingProcessStatus::STOPPED() !== $this->status) {
            throw new LogicException('The process is already running.');
        }

        $iso = $this->isolator();
        $iso->pcntl_signal(SIGINT,  [$this, 'stop'],    false);
        $iso->pcntl_signal(SIGTERM, [$this, 'stop'],    false);
        $iso->pcntl_signal(SIGHUP,  [$this, 'restart'], false);

        $this->transition(TickingProcessStatus::STARTING());

        try {
            do {
                if (TickingProcessStatus::STARTING() === $this->status) {
                    $this->initialize();
                } elseif (TickingProcessStatus::RUNNING() === $this->status) {
                    $this->tick();
                } elseif (TickingProcessStatus::RESTARTING() === $this->status) {
                    $this->shutdown();
                    $this->transition(TickingProcessStatus::STARTING());
                } elseif (TickingProcessStatus::ERROR() === $this->status) {
                    $this->shutdown();
                    @$iso->usleep(self::SLEEP_ON_ERROR * 1000000);
                    $this->transition(TickingProcessStatus::STARTING());
                } elseif (TickingProcessStatus::STOPPING() === $this->status) {
                    $this->shutdown();
                    break;
                }

                @$iso->pcntl_signal_dispatch();
                @$iso->usleep(self::SLEEP_ON_TICK * 1000000);
            } while (true);
        } catch (Exception $e) {
            $this->logger->error(
                'Unexpected exception ({message})',
                [
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            throw $e;
        } finally {
            $this->transition(TickingProcessStatus::STOPPED());

            $iso->pcntl_signal(SIGINT,  SIG_DFL);
            $iso->pcntl_signal(SIGTERM, SIG_DFL);
            $iso->pcntl_signal(SIGHUP,  SIG_DFL);
        }
    }

    /**
     * Instruct the process to stop.
     *
     * The process will stop on the next tick.
     */
    public function stop()
    {
        if (TickingProcessStatus::STOPPED() !== $this->status) {
            $this->transition(TickingProcessStatus::STOPPING());
        }
    }

    /**
     * Instruct the process to restart.
     *
     * The process will shutdown and re-initialize on the next tick.
     */
    public function restart()
    {
        if (TickingProcessStatus::STOPPED() !== $this->status) {
            $this->transition(TickingProcessStatus::RESTARTING());
        }
    }

    /**
     * Initialize the process.
     */
    private function initialize()
    {
        if ($this->action->initialize($this)) {
            $this->transition(TickingProcessStatus::RUNNING());
        } else {
            $this->transition(TickingProcessStatus::ERROR());
        }
    }

    /**
     * Shutdown the process.
     */
    private function shutdown()
    {
        $this->action->shutdown();
        $this->transition(TickingProcessStatus::STOPPED());
    }

    /**
     * Perform the action.
     */
    private function tick()
    {
        if (!$this->action->tick()) {
            $this->transition(TickingProcessStatus::ERROR());
        }
    }

    /**
     * Change the process status.
     *
     * @param TickingProcessStatus $status The new status.
     */
    private function transition(TickingProcessStatus $status)
    {
        if ($this->status === $status) {
            return;
        }

        $this->status = $status;

        if (TickingProcessStatus::STARTING() === $this->status) {
            $message = 'Process is starting';
        } elseif (TickingProcessStatus::RUNNING() === $this->status) {
            $message = 'Process is running';
        } elseif (TickingProcessStatus::RESTARTING() === $this->status) {
            $message = 'Process is restarting';
        } elseif (TickingProcessStatus::ERROR() === $this->status) {
            $message = sprintf(
                'Process is restarting after %s second(s) due to an error',
                round(self::SLEEP_ON_ERROR, 1)
            );
        } elseif (TickingProcessStatus::STOPPING() === $this->status) {
            $message = 'Process is stopping';
        } elseif (TickingProcessStatus::STOPPED() === $this->status) {
            $message = 'Process has stopped';
        }

        $this->logger->notice($message);
    }

    const SLEEP_ON_ERROR = 5;
    const SLEEP_ON_TICK = 0;

    use IsolatorTrait;

    private $action;
    private $logger;
    private $status;
}
