<?php
namespace Skewd\Server\Application;

use Eloquent\Phony\Phpunit\Phony;
use ErrorException;
use Exception;
use LogicException;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use Skewd\Common\Amqp\ConnectionFactory;
use Skewd\Server\Process\Process;

class ModularApplicationTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connectionFactory = Phony::fullMock(ConnectionFactory::class);
        $this->logger = Phony::fullMock(LoggerInterface::class);

        $this->subject = new ModularApplication(
            $this->connectionFactory->mock(),
            $this->logger->mock()
        );

        $this->channel1 = Phony::fullMock(AMQPChannel::class);
        $this->channel2 = Phony::fullMock(AMQPChannel::class);
        $this->channel3 = Phony::fullMock(AMQPChannel::class);

        $this->connection = Phony::fullMock(AMQPStreamConnection::class);
        $this->connection->isConnected->returns(true);
        $this->connection->channel->returns(
            $this->channel1->mock(),
            $this->channel2->mock(),
            $this->channel3->mock()
        );

        $this->connectionFactory->create->returns($this->connection->mock());

        $this->process = Phony::fullMock(Process::class);

        $this->module1 = Phony::fullMock(Module::class);
        $this->module1->name->returns('<module 1>');

        $this->module2 = Phony::fullMock(Module::class);
        $this->module2->name->returns('<module 2>');

        $this->module3 = Phony::fullMock(Module::class);
        $this->module3->name->returns('<module 3>');
    }

    public function testAdd()
    {
        $this->subject->add($this->module1->mock());

        $this->assertTrue(
            $this->subject->has($this->module1->mock())
        );
    }

    public function testRemove()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->remove($this->module1->mock());

        $this->assertFalse(
            $this->subject->has($this->module1->mock())
        );
    }

    public function testHas()
    {
        $this->assertFalse(
            $this->subject->has($this->module1->mock())
        );

        $this->assertFalse(
            $this->subject->has($this->module2->mock())
        );

        $this->subject->add($this->module1->mock());

        $this->assertTrue(
            $this->subject->has($this->module1->mock())
        );

        $this->assertFalse(
            $this->subject->has($this->module2->mock())
        );
    }

    public function testClear()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());

        $this->subject->clear();

        $this->assertFalse(
            $this->subject->has($this->module1->mock())
        );

        $this->assertFalse(
            $this->subject->has($this->module2->mock())
        );
    }

    public function testInitialize()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());

        $result = $this->subject->initialize($this->process->mock());

        Phony::inOrder(
            $this->connectionFactory->create->called(),
            Phony::anyOrder(
                $this->module1->initialize->calledWith(
                    $this->subject,
                    $this->channel1->mock()
                ),
                $this->module2->initialize->calledWith(
                    $this->subject,
                    $this->channel2->mock()
                )
            )
        );

        $this->assertTrue($result);
    }

    public function testInitializeWithConnectionFailure()
    {
        $exception = new Exception('Failed!');
        $this->connectionFactory->create->throws($exception);

        $result = $this->subject->initialize($this->process->mock());

        $this->logger->critical->calledWith(
            'Failed to establish AMQP connection: {message}',
            [
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testInitializeWithModuleFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->initialize->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $result = $this->subject->initialize($this->process->mock());

        $this->module1->initialize->calledWith(
            $this->subject,
            $this->channel1->mock()
        );
        $this->module2->initialize->calledWith(
            $this->subject,
            $this->channel2->mock()
        );
        $this->module3->initialize->never()->called();

        $this->logger->critical->calledWith(
            'Failed to initialize module "{name}": {message}',
            [
                'name' => '<module 2>',
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testShutdown()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());

        $result = $this->subject->shutdown();

        $this->module1->shutdown->called();
        $this->module2->shutdown->called();

        $this->connection->close->never()->called();

        $this->assertTrue($result);
    }

    public function testShutdownClosesConnection()
    {
        $this->subject->initialize($this->process->mock());

        $result = $this->subject->shutdown();

        $this->connection->close->called();

        $this->assertTrue($result);
    }

    public function testShutdownDoesNotCloseAlreadyClosedConnection()
    {
        $this->connection->isConnected->returns(false);

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->shutdown();

        $this->connection->close->never()->called();

        $this->assertTrue($result);
    }

    public function testShutdownWithConnectionCloseFailure()
    {
        $exception = new Exception('Failed!');
        $this->connection->close->throws($exception);

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->shutdown();

        $this->logger->warning->calledWith(
            'Failed to close AMQP connection gracefully: {message}',
            [
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testShutdownWithModuleFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->shutdown->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $result = $this->subject->shutdown();

        $this->module1->shutdown->called();
        $this->module2->shutdown->called();
        $this->module3->shutdown->called();

        $this->logger->warning->calledWith(
            'Failed to shut down module "{name}": {message}',
            [
                'name' => '<module 2>',
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testTick()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->tick();

        Phony::inOrder(
            $this->connection->select->calledWith(0, 100000),
            Phony::anyOrder(
                $this->module1->tick->called(),
                $this->module2->tick->called(),
                $this->channel1->wait->calledWith(null, true, 0.000001),
                $this->channel2->wait->calledWith(null, true, 0.000001)
            )
        );

        $this->assertTrue($result);
    }

    public function testTickWithoutInitialization()
    {
        $this->setExpectedException(
            LogicException::class,
            'Application has not been initialized.'
        );

        $this->subject->tick();
    }

    public function testTickWithConnectionSelectInteruptedBySignal()
    {
        $exception = new ErrorException('Interrupted system call');
        $this->connection->select->throws($exception);

        $this->subject->add($this->module1->mock());

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->tick();

        $this->module1->tick->never()->called();

        $this->assertTrue($result);
    }

    public function testTickWithConnectionSelectFailure()
    {
        $exception = new Exception('Failed!');
        $this->connection->select->throws($exception);

        $this->subject->add($this->module1->mock());

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->tick();

        $this->module1->tick->never()->called();

        $this->logger->critical->calledWith(
            'Failure while waiting for AMQP connection: {message}',
            [
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testTickWithModuleFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->tick->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->tick();

        $this->module1->tick->called();
        $this->module2->tick->called();
        $this->module3->tick->never()->called();

        $this->logger->critical->calledWith(
            'Failure in module "{name}": {message}',
            [
                'name' => '<module 2>',
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }
}
