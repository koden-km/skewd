<?php
namespace Skewd\Common\Amqp;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel as AMQPChannelImpl;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Skewd\Common\Messaging\ConnectionException;
use Skewd\Common\Messaging\NodeIdGenerator;

class AmqpNodeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestSkipped();

        $this->channelA = Phony::fullMock(AMQPChannelImpl::class);
        $this->channelB = Phony::fullMock(AMQPChannelImpl::class);
        $this->channelC = Phony::fullMock(AMQPChannelImpl::class);
        $this->channelD = Phony::fullMock(AMQPChannelImpl::class);
        $this->channelE = Phony::fullMock(AMQPChannelImpl::class);

        $this->connection = Phony::fullMock(AbstractConnection::class);
        $this->connection->isConnected->returns(true);
        $this->connection->channel->returns(
            $this->channelA->mock(),
            $this->channelB->mock(),
            $this->channelC->mock(),
            $this->channelD->mock(),
            $this->channelE->mock()
        );

        $this->connector = Phony::fullMock(Connector::class);
        $this->connector->connect->returns($this->connection->mock());

        $this->nodeIdGenerator = Phony::fullMock(NodeIdGenerator::class);
        $this->nodeIdGenerator->generate->returns([
            'aaaa',
            'bbbb',
            'cccc',
            'dddd',
            'eeee',
        ]);

        // This was the easiest way to create one of these exceptions because
        // of some mad static data inside the AMQP exception class that only
        // exists after a connection has been made
        $this->resourceLockedException = Phony::mock(
            [AMQPExceptionInterface::class, Exception::class],
            ['', 405]
        );
        $this->otherAmqpException = Phony::mock(
            [AMQPExceptionInterface::class, Exception::class],
            ['', 0]
        );

        $this->subject = AmqpNode::create(
            $this->connector->mock(),
            $this->nodeIdGenerator->mock()
        );
    }

    public function testIdIsClearedIfConnectionIsBroken()
    {
        $this->subject->connect();

        $this->connection->isConnected->returns(false);

        $this->assertNull(
            $this->subject->id()
        );
    }

    public function testConnect()
    {
        $this->assertNull(
            $this->subject->id()
        );

        $this->subject->connect();

        $this->connector->connect->called();

        $this->assertSame(
            'aaaa',
            $this->subject->id()
        );
    }

    public function testConnectWithConnectorException()
    {
        $this->connector->connect->throws($this->otherAmqpException->mock());

        $this->setExpectedException(ConnectionException::class);

        try {
            $this->subject->connect();
        } catch (ConnectionException $e) {
            $this->assertNull(
                $this->subject->id()
            );

            $this->assertSame(
                $this->otherAmqpException->mock(),
                $e->getPrevious()
            );

            throw $e;
        }
    }

    public function testConnectReservesNodeIdUsingQueue()
    {
        $this->subject->connect();

        $this->channelA->queue_declare->calledWith(
            'node-aaaa',
            false, // passive
            false, // durable
            true   // exclusive
        );
    }

    public function testConnectRetriesIdReservation()
    {
        $this->channelA->queue_declare->throws($this->resourceLockedException->mock());

        $this->subject->connect();

        Phony::inOrder(
            $this->channelA->queue_declare->calledWith(
                'node-aaaa',
                false, // passive
                false, // durable
                true   // exclusive
            ),
            $this->channelB->queue_declare->calledWith(
                'node-bbbb',
                false, // passive
                false, // durable
                true   // exclusive
            )
        );

        $this->assertSame(
            'bbbb',
            $this->subject->id()
        );
    }

    public function testConnectRetriesIdReservationUpToFiveTimes()
    {
        $this->channelA->queue_declare->throws($this->resourceLockedException->mock());
        $this->channelB->queue_declare->throws($this->resourceLockedException->mock());
        $this->channelC->queue_declare->throws($this->resourceLockedException->mock());
        $this->channelD->queue_declare->throws($this->resourceLockedException->mock());
        $this->channelE->queue_declare->throws($this->resourceLockedException->mock());

        $this->setExpectedException(ConnectionException::class);

        try {
            $this->subject->connect();
        } catch (ConnectionException $e) {
            Phony::inOrder(
                $this->channelA->queue_declare->called(),
                $this->channelB->queue_declare->called(),
                $this->channelC->queue_declare->called(),
                $this->channelD->queue_declare->called(),
                $this->channelE->queue_declare->called()
            );

            $this->assertSame(
                $this->resourceLockedException->mock(),
                $e->getPrevious()
            );

            throw $e;
        }
    }

    public function testConnectWithQueueDeclareException()
    {
        $this->channelA->queue_declare->throws($this->otherAmqpException->mock());

        $this->setExpectedException(ConnectionException::class);

        try {
            $this->subject->connect();
        } catch (ConnectionException $e) {
            $this->assertSame(
                $this->otherAmqpException->mock(),
                $e->getPrevious()
            );

            throw $e;
        }
    }

    public function testConnectDisconnectsFirst()
    {
        $this->subject->connect();

        $this->subject->connect();

        Phony::inOrder(
            $this->connector->connect->called(),
            $this->connection->close->called(),
            $this->connector->connect->called()
        );
    }

    public function testDisconnect()
    {
        $this->subject->connect();

        $this->subject->disconnect();

        $this->connection->close->called();

        $this->assertNull(
            $this->subject->id()
        );
    }

    public function testDisconnectWhenAlreadyDisconnected()
    {
        $this->subject->disconnect();

        $this->connection->noInteraction();

        $this->assertNull(
            $this->subject->id()
        );
    }

    public function testIsConnected()
    {
        $this->assertFalse(
            $this->subject->isConnected()
        );

        $this->subject->connect();

        $this->assertTrue(
            $this->subject->isConnected()
        );

        $this->connection->isConnected->returns(false);

        $this->assertFalse(
            $this->subject->isConnected()
        );
    }

    public function testCreateChannel()
    {
        $this->subject->connect();

        $channel = new AmqpChannel($this->channelB->mock());

        $this->assertEquals(
            $channel,
            $this->subject->createChannel()
        );
    }

    public function testWait()
    {
        $this->markTestIncomplete();
    }
}
