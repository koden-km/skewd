<?php
namespace Skewd\Common\Lock;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

class AmqpLockManagerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->channel = Phony::fullMock(AMQPChannel::class);
        $this->logger = Phony::fullMock(LoggerInterface::class);

        $this->subject = AmqpLockManager::create(
            $this->channel->mock(),
            $this->logger->mock()
        );
    }

    public function testLock()
    {
        $lock = $this->subject->lock('<resource>');

        $this->channel->queue_declare->calledWith(
            'lock/<resource>',
            false, // passive
            false, // durable
            true   // exclusive
        );

        $this->logger->debug->calledWith(
            'Locked resource "{resource}"',
            ['resource' => '<resource>']
        );

        $this->logger->debug->once()->called();

        $this->assertInstanceOf(
            ScopedLock::class,
            $lock
        );

        $this->channel->queue_delete->never()->called();

        $lock = false;

        $this->channel->queue_delete->calledWith(
            'lock/<resource>'
        );

        $this->logger->debug->calledWith(
            'Unlocked resource "{resource}"',
            ['resource' => '<resource>']
        );
    }

    public function testLockFailure()
    {
        // The AMQPProtocolChannelException constructor accesses global data
        // that is not initialized unless there is an actual connection (gross).
        //
        // Bypass its constructor and inject the code we need using reflection.
        $exception = Phony::fullMock(AMQPProtocolChannelException::class, null)->mock();

        $reflector = new ReflectionProperty(AMQPProtocolChannelException::class, 'code');
        $reflector->setAccessible(true);
        $reflector->setValue($exception, 405); // AMQP resource locked code.

        $this->channel->queue_declare->throws($exception);

        $this->setExpectedException(
            LockException::class,
            'Failed to lock resource '
        );

        try {
            $this->subject->lock('<resource>');
        } catch (LockException $e) {
            $this->assertSame(
                $exception,
                $e->getPrevious()
            );

            $this->logger->debug->calledWith(
                'Failed to lock resource "{resource}"',
                ['resource' => '<resource>']
            );

            throw $e;
        }
    }

    public function testLockFailureWithOtherAMQPException()
    {
        // The AMQPProtocolChannelException constructor accesses global data
        // that is not initialized unless there is an actual connection (gross).
        $exception = Phony::fullMock(AMQPProtocolChannelException::class, null)->mock();

        $this->channel->queue_declare->throws($exception);

        $this->setExpectedException(AMQPProtocolChannelException::class);

        $this->subject->lock('<resource>');
    }

    public function testUnlockFailure()
    {
        $exception = Phony::fullMock(
            [
                AMQPExceptionInterface::class,
                Exception::class,
            ],
            null
        )->mock();

        $this->channel->queue_delete->throws($exception);

        // let scoped lock destruct immediately ...
        $this->subject->lock('<resource>');

        $this->logger->debug->calledWith(
            'Failed to unlock resource "{resource}" gracefully',
            [
                'resource' => '<resource>',
                'exception' => $exception,
            ]
        );
    }
}
