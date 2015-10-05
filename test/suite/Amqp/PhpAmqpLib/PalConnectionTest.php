<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
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

    public function testCloseOnDestruct()
    {
        $this->subject = null;
        $this->connection->close->called();
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
            ConnectionWaitResult::NORMAL(),
            $result
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

    public function testWaitDispatchesBeforeSelect()
    {
        $this->connection->mock()->channels[1] = $this->channel->mock();

        $result = $this->subject->wait(0);

        $this->channel->wait->calledWith(
            null, // allowed methods
            true, // non-blocking
            1e-7  // timeout - must be non-zero, see comments in PalConnection::wait()
        );

        $this->connection->select->never()->called();

        $this->assertSame(
            ConnectionWaitResult::NORMAL(),
            $result
        );
    }

    public function testWaitDispatchesAfterSelect()
    {
        $this->connection->mock()->channels[1] = $this->channel->mock();

        // Make the channel timeout during the first dispatch(), so that wait
        // does not bail early and instead proceeds to the select ...
        $this
            ->channel
            ->wait
            ->throws(new AMQPTimeoutException())
            ->returns(null);

        $this->connection->select->returns(1);

        $result = $this->subject->wait(0);

        $this->channel->wait->calledWith(
            null, // allowed methods
            true, // non-blocking
            1e-7  // timeout - must be non-zero, see comments in PalConnection::wait()
        );

        $this->connection->select->called();

        $this->assertSame(
            ConnectionWaitResult::NORMAL(),
            $result
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
