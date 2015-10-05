<?php
namespace Skewd\Lock;

use Eloquent\Phony\Phpunit\Phony;
use PHPUnit_Framework_TestCase;
use Skewd\Amqp\Channel;
use Skewd\Amqp\Connection\Connection;
use Skewd\Amqp\Consumer;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\Queue;
use Skewd\Amqp\QueueParameter;
use Skewd\Amqp\ResourceLockedException;
use Skewd\Amqp\ResourceNotFoundException;

class AmqpResourceTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phony::fullMock(Connection::class);
        $this->channel = Phony::fullMock(Channel::class);
        $this->queue = Phony::fullMock(Queue::class);
        $this->consumer = Phony::fullMock(Consumer::class);

        $this->connection->channel->returns($this->channel->mock());
        $this->channel->queue->returns($this->queue->mock());
        $this->queue->consume->returns($this->consumer->mock());

        $this->subject = new AmqpResource(
            $this->connection->mock(),
            '<name>'
        );
    }

    public function lockModes()
    {
        return [
            // mode                 // expected consumer params
            [LockMode::SHARED(),     []],
            [LockMode::EXCLUSIVE(), [ConsumerParameter::EXCLUSIVE()]],
        ];
    }

    /**
     * @dataProvider lockModes
     */
    public function testAcquireLock(LockMode $mode, array $consumerParameters)
    {
        $result = $this->subject->acquireLock($mode);

        $this->channel->queue->calledWith(
            'lock.<name>',
            [QueueParameter::AUTO_DELETE()]
        );

        $this->queue->consume->calledWith(
            '~', // we don't care about the handler, no messages are published to lock queues
            $consumerParameters
        );

        $this->assertEquals(
            Lock::create(
                $mode,
                [$this->consumer->mock(), 'cancel']
            ),
            $result
        );
    }

    /**
     * @dataProvider lockModes
     */
    public function testAcquireLockFailure(LockMode $mode)
    {
        $this->queue->consume->throws(
            ResourceLockedException::queueHasExclusiveConsumer('<queue-name>')
        );

        $this->setExpectedException(
            LockException::class,
            'Failed to acquire ' . $mode . ' lock on "<name>", resource is already locked.'
        );

        $this->subject->acquireLock($mode);
    }

    public function testAcquireLockRetriesWhenQueueNotFound()
    {
        $this
            ->queue
            ->consume
            ->throws(ResourceNotFoundException::queueNotFound('<queue-name>'))
            ->returns($this->consumer->mock());

        $result = $this->subject->acquireLock(LockMode::EXCLUSIVE());

        Phony::inOrder(
            $this->connection->channel->called(),
            $this->channel->queue->called(),
            $this->queue->consume->called(),
            $this->connection->channel->called(),
            $this->channel->queue->called(),
            $this->queue->consume->called()
        );

        $this->assertEquals(
            Lock::create(
                LockMode::EXCLUSIVE(),
                [$this->consumer->mock(), 'cancel']
            ),
            $result
        );
    }

    /**
     * @dataProvider lockModes
     */
    public function testTryAcquireLock(LockMode $mode)
    {
        $lock = null;
        $result = $this->subject->tryAcquireLock($mode, $lock);

        $this->assertEquals(
            Lock::create(
                $mode,
                [$this->consumer->mock(), 'cancel']
            ),
            $lock
        );

        $this->assertTrue($result);
    }

    /**
     * @dataProvider lockModes
     */
    public function testTryAcquireLockFailure(LockMode $mode)
    {
        $this->queue->consume->throws(
            ResourceLockedException::queueHasExclusiveConsumer('<queue-name>')
        );

        $lock = null;
        $result = $this->subject->tryAcquireLock($mode, $lock);

        $this->assertNull($lock);
        $this->assertFalse($result);
    }

    public function testTryAcquireLockSetsExistingLockToNullOnFailure()
    {
        $this->queue->consume->throws(
            ResourceLockedException::queueHasExclusiveConsumer('<queue-name>')
        );

        $lock = Lock::create(LockMode::EXCLUSIVE(), function () {});

        $this->subject->tryAcquireLock(LockMode::EXCLUSIVE(), $lock);

        $this->assertNull($lock);
    }
}
