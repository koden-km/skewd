<?php
namespace Skewd\Server;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use Icecave\Isolator\Isolator;
use LogicException;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;

class ModularServerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->logger = Phony::fullMock(LoggerInterface::class);
        $this->modules = Phony::fullMock(ModuleCollection::class);
        $this->isolator = Phony::fullMock(Isolator::class);

        $this->modules->initialize->returns(true);
        $this->modules->shutdown->returns(true);

        // Shut down the server after the second tick ...
        $this->modules->tick
            ->returns(true)
            ->does(
                function () {
                    $this->subject->stop();

                    return true;
                }
            );

        $this->subject = new ModularServer(
            $this->logger->mock(),
            $this->modules->mock()
        );

        $this->subject->setIsolator($this->isolator->mock());
    }

    public function testModules()
    {
        $this->assertSame(
            $this->modules->mock(),
            $this->subject->modules()
        );
    }

    public function testDefaultModules()
    {
        $this->subject = new ModularServer(
            $this->logger->mock()
        );

        $this->assertInstanceOf(
            OrderedModuleCollection::class,
            $this->subject->modules()
        );
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
            $this->logger->notice->calledWith('Server is starting'),
            $this->modules->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Server is running'),
            $this->isolator->pcntl_signal_dispatch->called(),
            $this->isolator->usleep->calledWith(0),
            $this->modules->tick->called(),
            $this->isolator->pcntl_signal_dispatch->called(),
            $this->isolator->usleep->calledWith(0),
            $this->modules->tick->called(),
            $this->logger->notice->calledWith('Server is stopping'),
            $this->isolator->pcntl_signal_dispatch->called(),
            $this->isolator->usleep->calledWith(0),
            $this->modules->shutdown->called(),
            $this->logger->notice->calledWith('Server has stopped'),
            Phony::anyOrder(
                $this->isolator->pcntl_signal->calledWith(SIGINT,  SIG_DFL),
                $this->isolator->pcntl_signal->calledWith(SIGTERM, SIG_DFL),
                $this->isolator->pcntl_signal->calledWith(SIGHUP,  SIG_DFL)
            )
        );
    }

    public function testStartWithInitializationFailure()
    {
        $this->modules->initialize->returns(false, true);

        $this->subject->start();

        Phony::inOrder(
            $this->logger->notice->calledWith('Server is starting'),
            $this->modules->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Server is restarting after 5 second(s) due to an error'),
            $this->logger->notice->calledWith('Server has stopped'),
            $this->isolator->usleep->calledWith(5000000),
            $this->logger->notice->calledWith('Server is starting'),
            $this->modules->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Server is running'),
            $this->modules->tick->called(),
            $this->modules->tick->called(),
            $this->logger->notice->calledWith('Server is stopping'),
            $this->modules->shutdown->called(),
            $this->logger->notice->calledWith('Server has stopped')
        );
    }

    public function testStartWithTickFailure()
    {
        $this->modules->tick
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
            $this->logger->notice->calledWith('Server is starting'),
            $this->modules->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Server is running'),
            $this->modules->tick->called(),
            $this->logger->notice->calledWith('Server is restarting after 5 second(s) due to an error'),
            $this->logger->notice->calledWith('Server has stopped'),
            $this->isolator->usleep->calledWith(5000000),
            $this->logger->notice->calledWith('Server is starting'),
            $this->modules->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Server is running'),
            $this->modules->tick->called(),
            $this->modules->tick->called(),
            $this->logger->notice->calledWith('Server is stopping'),
            $this->modules->shutdown->called(),
            $this->logger->notice->calledWith('Server has stopped')
        );
    }

    public function testStartWhenAlreadyRunning()
    {
        $this->modules->initialize->does(
            [$this->subject, 'start']
        );

        $this->setExpectedException(
            LogicException::class,
            'The server is already running.'
        );

        $this->subject->start();
    }

    public function testStartWithException()
    {
        $exception = new Exception('Failed!');

        $this->modules->tick->throws($exception);

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
        $this->modules->tick
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
            $this->logger->notice->calledWith('Server is starting'),
            $this->modules->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Server is running'),
            $this->modules->tick->called(),
            $this->logger->notice->calledWith('Server is restarting'),
            $this->modules->shutdown->called(),
            $this->logger->notice->calledWith('Server has stopped'),
            $this->logger->notice->calledWith('Server is starting'),
            $this->modules->initialize->calledWith($this->subject),
            $this->logger->notice->calledWith('Server is running'),
            $this->modules->tick->called(),
            $this->logger->notice->calledWith('Server is stopping'),
            $this->modules->shutdown->called(),
            $this->logger->notice->calledWith('Server has stopped')
        );
    }
}
