<?php
namespace Skewd\Server;

use Exception;
use Icecave\Isolator\IsolatorTrait;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * A server with functionality provided by modules.
 *
 * @see Module
 * @see ModuleCollection
 */
final class ModularServer implements Server
{
    use IsolatorTrait;

    /**
     * @param LoggerInterface       $logger  The logger to use for all server output.
     * @param ModuleCollection|null $modules The collection of modules to use, or null to create a new collection.
     */
    public function __construct(
        LoggerInterface $logger,
        ModuleCollection $modules = null
    ) {
        $this->logger = $logger;
        $this->modules = $modules ?: new OrderedModuleCollection($logger);
        $this->status = ServerStatus::STOPPED();
    }

    /**
     * Get the server's module collection.
     *
     * @return ModuleCollection The collection of modules used by the server.
     */
    public function modules()
    {
        return $this->modules;
    }

    /**
     * Start the server and loop until it is stopped.
     *
     * @throws LogicException if the server has already been started.
     */
    public function start()
    {
        if (ServerStatus::STOPPED() !== $this->status) {
            throw new LogicException('The server is already running.');
        }

        $iso = $this->isolator();
        $iso->pcntl_signal(SIGINT,  [$this, 'stop'],    false);
        $iso->pcntl_signal(SIGTERM, [$this, 'stop'],    false);
        $iso->pcntl_signal(SIGHUP,  [$this, 'restart'], false);

        $this->setStatus(ServerStatus::STARTING());

        try {
            do {
                if (ServerStatus::STARTING() === $this->status) {
                    $this->initialize();
                } elseif (ServerStatus::RUNNING() === $this->status) {
                    $this->tick();
                } elseif (ServerStatus::RESTARTING() === $this->status) {
                    $this->shutdown();
                    $this->setStatus(ServerStatus::STARTING());
                } elseif (ServerStatus::ERROR() === $this->status) {
                    $this->shutdown();
                    @$iso->usleep($this->errorSleep * 1000000);
                    $this->setStatus(ServerStatus::STARTING());
                } elseif (ServerStatus::STOPPING() === $this->status) {
                    $this->shutdown();
                    break;
                }

                @$iso->pcntl_signal_dispatch();
                @$iso->usleep($this->tickSleep * 1000000);
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
            $this->setStatus(ServerStatus::STOPPED());

            $iso->pcntl_signal(SIGINT,  SIG_DFL);
            $iso->pcntl_signal(SIGTERM, SIG_DFL);
            $iso->pcntl_signal(SIGHUP,  SIG_DFL);
        }
    }

    /**
     * Instruct the server to stop.
     *
     * The server will stop on the next tick.
     */
    public function stop()
    {
        if (ServerStatus::STOPPED() !== $this->status) {
            $this->setStatus(ServerStatus::STOPPING());
        }
    }

    /**
     * Instruct the server to restart.
     *
     * The server will shutdown and re-initialize on the next tick.
     */
    public function restart()
    {
        if (ServerStatus::STOPPED() !== $this->status) {
            $this->setStatus(ServerStatus::RESTARTING());
        }
    }

    /**
     * Initialize all modules.
     */
    private function initialize()
    {
        if ($this->modules->initialize($this)) {
            $this->setStatus(ServerStatus::RUNNING());
        } else {
            $this->setStatus(ServerStatus::ERROR());
        }
    }

    /**
     * Shutdown all modules.
     */
    private function shutdown()
    {
        $this->modules->shutdown();
        $this->setStatus(ServerStatus::STOPPED());
    }

    /**
     * Call tick on all modules.
     */
    private function tick()
    {
        if (!$this->modules->tick()) {
            $this->setStatus(ServerStatus::ERROR());
        }
    }

    /**
     * Change the server status.
     *
     * @param ServerStatus $status The new status.
     */
    private function setStatus(ServerStatus $status)
    {
        if ($this->status === $status) {
            return;
        }

        $this->status = $status;

        if (ServerStatus::STARTING() === $this->status) {
            $message = 'Server is starting';
        } elseif (ServerStatus::RUNNING() === $this->status) {
            $message = 'Server is running';
        } elseif (ServerStatus::RESTARTING() === $this->status) {
            $message = 'Server is restarting';
        } elseif (ServerStatus::ERROR() === $this->status) {
            $message = sprintf(
                'Server is restarting after %s second(s) due to an error',
                round($this->errorSleep, 1)
            );
        } elseif (ServerStatus::STOPPING() === $this->status) {
            $message = 'Server is stopping';
        } elseif (ServerStatus::STOPPED() === $this->status) {
            $message = 'Server has stopped';
        }

        $this->logger->notice($message);
    }

    private $logger;
    private $modules;
    private $status;
    private $errorSleep = 5;
    private $tickSleep = 0;
}
