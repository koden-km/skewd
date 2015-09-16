<?php
namespace Skewd\Process;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use Icecave\Isolator\Isolator;
use LogicException;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;

class TickingProcessTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->action = Phony::fullMock(TickingProcessAction::class);
        $this->logger = Phony::fullMock(LoggerInterface::class);
        $this->isolator = Phony::fullMock(Isolator::class);

        $this->action->initialize->returns(true);
        $this->action->shutdown->returns(true);

        // Shut down the process after the second tick ...
        $this->action->tick
            ->returns(true)
            ->does(
                function () {
                    $this->subject->stop();

                    return true;
                }
            );

        $this->subject = new TickingProcess(
            $this->action->mock(),
            $this->logger->mock()
        );

        $this->subject->setIsolator($this->isolator->mock());
    }

    public function testStart()
    {
        $this->subject->start();

        Phony::inOrder(
            Phony::anyOrder(
                $this->isolator->pcntl_signal->calledWith(SIGINT,  [$this->subject, 'stop'], false),
                $this->isolator->pcntl_signal->calledWith(SIGTERM, [$this->subject, 'stop'], false),
                $this->isolator->pcntl_signal->calledWith(SIGHUP,  [$this->subject, 'restart'], false)
            ),
            $this->logger->notice->calledWith('Process is starting'),
            $this->action->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Process is running'),
            $this->isolator->pcntl_signal_dispatch->called(),
            $this->isolator->usleep->calledWith(0),
            $this->action->tick->called(),
            $this->isolator->pcntl_signal_dispatch->called(),
            $this->isolator->usleep->calledWith(0),
            $this->action->tick->called(),
            $this->logger->notice->calledWith('Process is stopping'),
            $this->isolator->pcntl_signal_dispatch->called(),
            $this->isolator->usleep->calledWith(0),
            $this->action->shutdown->called(),
            $this->logger->notice->calledWith('Process has stopped'),
            Phony::anyOrder(
                $this->isolator->pcntl_signal->calledWith(SIGINT,  SIG_DFL),
                $this->isolator->pcntl_signal->calledWith(SIGTERM, SIG_DFL),
                $this->isolator->pcntl_signal->calledWith(SIGHUP,  SIG_DFL)
            )
        );
    }

    public function testStartWithInitializationFailure()
    {
        $this->action->initialize->returns(false, true);

        $this->subject->start();

        Phony::inOrder(
            $this->logger->notice->calledWith('Process is starting'),
            $this->action->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Process is restarting after 5 second(s) due to an error'),
            $this->logger->notice->calledWith('Process has stopped'),
            $this->isolator->usleep->calledWith(5000000),
            $this->logger->notice->calledWith('Process is starting'),
            $this->action->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Process is running'),
            $this->action->tick->called(),
            $this->action->tick->called(),
            $this->logger->notice->calledWith('Process is stopping'),
            $this->action->shutdown->called(),
            $this->logger->notice->calledWith('Process has stopped')
        );
    }

    public function testStartWithTickFailure()
    {
        $this->action->tick
            ->returns(false)
            ->returns(true)
            ->does(
                function () {
                    $this->subject->stop();

                    return true;
                }
            );

        $this->subject->start();

        Phony::inOrder(
            $this->logger->notice->calledWith('Process is starting'),
            $this->action->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Process is running'),
            $this->action->tick->called(),
            $this->logger->notice->calledWith('Process is restarting after 5 second(s) due to an error'),
            $this->logger->notice->calledWith('Process has stopped'),
            $this->isolator->usleep->calledWith(5000000),
            $this->logger->notice->calledWith('Process is starting'),
            $this->action->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Process is running'),
            $this->action->tick->called(),
            $this->action->tick->called(),
            $this->logger->notice->calledWith('Process is stopping'),
            $this->action->shutdown->called(),
            $this->logger->notice->calledWith('Process has stopped')
        );
    }

    public function testStartWhenAlreadyRunning()
    {
        $this->action->initialize->does(
            [$this->subject, 'start']
        );

        $this->setExpectedException(
            LogicException::class,
            'The process is already running.'
        );

        $this->subject->start();
    }

    public function testStartWithException()
    {
        $exception = new Exception('Failed!');

        $this->action->tick->throws($exception);

        $this->setExpectedException(
            Exception::class,
            'Failed!'
        );

        try {
            $this->subject->start();
        } catch (Exception $e) {
            $this->logger->error->calledWith(
                'Unexpected exception ({message})',
                [
                    'message' => 'Failed!',
                    'exception' => $exception,
                ]
            );

            throw $e;
        }
    }

    public function testRestart()
    {
        $this->action->tick
            ->does(
                function () {
                    $this->subject->restart();

                    return true;
                },
                function () {
                    $this->subject->stop();

                    return true;
                }
            );

        $this->subject->start();

        Phony::inOrder(
            $this->logger->notice->calledWith('Process is starting'),
            $this->action->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Process is running'),
            $this->action->tick->called(),
            $this->logger->notice->calledWith('Process is restarting'),
            $this->action->shutdown->called(),
            $this->logger->notice->calledWith('Process has stopped'),
            $this->logger->notice->calledWith('Process is starting'),
            $this->action->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Process is running'),
            $this->action->tick->called(),
            $this->logger->notice->calledWith('Process is stopping'),
            $this->action->shutdown->called(),
            $this->logger->notice->calledWith('Process has stopped')
        );
    }
}
