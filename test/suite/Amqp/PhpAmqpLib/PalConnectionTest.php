<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Skewd\Amqp\Connection\ConnectionException;
use Skewd\Amqp\Connection\ConnectionWaitResult;

class PalConnectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->channel = Phony::fullMock(AMQPChannel::class);
        $this->connection = Phony::fullMock(AbstractConnection::class);
        $this->connection->isConnected->returns(true);
        $this->connection->channel->returns($this->channel->mock());

        $this->subject = new PalConnection(
            $this->connection->mock()
        );
    }

    public function testIsConnected()
    {
        $this->assertTrue(
            $this->subject->isConnected()
        );

        $this->connection->isConnected->returns(false);

        $this->assertFalse(
            $this->subject->isConnected()
        );
    }

    public function testClose()
    {
        $this->subject->close();

        $this->connection->close->called();

        $this->assertFalse(
            $this->subject->isConnected()
        );
    }

    public function testCloseCanBeCalledRepeatedly()
    {
        $this->subject->close();
        $this->subject->close();

        $this->connection->close->once()->called();
    }

    public function testChannel()
    {
        $result = $this->subject->channel();

        $this->assertEquals(
            new PalChannel(
                $this->channel->mock()
            ),
            $result
        );
    }

    public function testChannelWhenDisconnected()
    {
        $this->subject->close();

        $this->setExpectedException(
            ConnectionException::class,
            'Disconnected from AMQP server.'
        );

        $this->subject->channel();
    }

    public function testChannelWithExceptionThatCausesDisconnection()
    {
        $exception = new Exception('The exception!');

        $this->connection->channel->does(
            function () use ($exception) {
                $this->connection->isConnected->returns(false);

                throw $exception;
            }
        );

        $this->setExpectedException(
            ConnectionException::class,
            'Disconnected from AMQP server.'
        );

        $this->subject->channel();
    }

    public function testChannelWithExceptionPropagatesUnchanged()
    {
        $this->connection->channel->throws(
            new Exception('The exception!')
        );

        $this->setExpectedException(
            Exception::class,
            'The exception!'
        );

        $this->subject->channel();
    }

    public function testWait()
    {
        $this->connection->select->returns(1);

        $result = $this->subject->wait(1.5);

        $this->connection->select->calledWith(0, 1500000);

        $this->channel->noInteraction();

        $this->assertSame(
            ConnectionWaitResult::READY(),
            $result
        );
    }

    public function testWaitCallsWaitOnInternalChannels()
    {
        $this->connection->select->returns(1);

        $this->connection->mock()->channels[1] = $this->channel->mock();

        $this->subject->wait(0);

        $this->channel->wait->calledWith(
            null, // allowed methods
            true  // non-blocking
        );
    }

    public function testWaitTimeout()
    {
        $this->connection->select->returns(0);

        $this->assertSame(
            ConnectionWaitResult::TIMEOUT(),
            $this->subject->wait(0)
        );
    }

    public function testWaitInterruptedBySignal()
    {
        $this->connection->select->returns(false);

        $this->assertSame(
            ConnectionWaitResult::SIGNAL(),
            $this->subject->wait(0)
        );
    }

    public function testWaitWhenDisconnected()
    {
        $this->subject->close();

        $this->setExpectedException(
            ConnectionException::class,
            'Disconnected from AMQP server.'
        );

        $this->subject->wait(0);
    }
}
