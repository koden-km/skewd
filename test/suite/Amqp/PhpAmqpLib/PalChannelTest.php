<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use LogicException;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use ReflectionProperty;
use Skewd\Amqp\DeclareException;
use Skewd\Amqp\ExchangeParameter;
use Skewd\Amqp\ExchangeType;
use Skewd\Amqp\QueueParameter;
use Skewd\Amqp\ResourceLockedException;

class PalChannelTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->channel = Phony::fullMock(AMQPChannel::class);
        $this->channel->queue_declare->does(
            function ($name) {
                if ($name === '') {
                    return ['<server-generated>'];
                }

                return [$name];
            }
        );

        $this->subject = new PalChannel(
            $this->channel->mock()
        );
    }

    public function exchangeTypes()
    {
        return [
            // type                   php-amqplib type   pre-declared exchange name
            [ExchangeType::DIRECT(),  'direct',         'amq.direct'],
            [ExchangeType::FANOUT(),  'fanout',         'amq.fanout'],
            [ExchangeType::TOPIC(),   'topic',          'amq.topic'],
            [ExchangeType::HEADERS(), 'headers',        'amq.headers'],
        ];
    }

    /**
     * @dataProvider exchangeTypes
     */
    public function testExchange(ExchangeType $type, $palType)
    {
        $result = $this->subject->exchange(
            '<name>',
            $type
        );

        $this->channel->exchange_declare->calledWith(
            '<name>',
            $palType,
            false, // passive
            false, // durable
            true,  // auto-delete
            false  // internal
        );

        $this->assertEquals(
            new PalExchange(
                '<name>',
                $type,
                ExchangeParameter::normalize(null), // defaults
                $this->channel->mock()
            ),
            $result
        );
    }

    public function testExchangeWithParameters()
    {
        // Test with the inverse of the default properties to cover on/off states ...
        $properties = [
            ExchangeParameter::PASSIVE(),
            ExchangeParameter::DURABLE(),
            ExchangeParameter::INTERNAL(),
        ];

        $result = $this->subject->exchange(
            '<name>',
            ExchangeType::DIRECT(),
            $properties
        );

        $this->channel->exchange_declare->calledWith(
            '<name>',
            'direct',
            true,  // passive
            true,  // durable
            false, // auto-delete
            true   // internal
        );

        $this->assertEquals(
            new PalExchange(
                '<name>',
                ExchangeType::DIRECT(),
                ExchangeParameter::normalize($properties),
                $this->channel->mock()
            ),
            $result
        );
    }

    public function testExchangeWithDeclarationFailure()
    {
        // The AMQPProtocolChannelException constructor accesses global data
        // which is not initialized unless there is an actual connection which
        // makes it difficult to mock. Here, we bypass the constructor entirely
        // and set the mocked exception code using reflection.
        $exception = Phony::fullMock(AMQPProtocolChannelException::class, null)->mock();

        $reflector = new ReflectionProperty(AMQPProtocolChannelException::class, 'code');
        $reflector->setAccessible(true);
        $reflector->setValue($exception, AmqpConstant::PRECONDITION_FAILED);

        $this->channel->exchange_declare->throws($exception);

        $this->setExpectedException(
            DeclareException::class,
            'Failed to declare exchange "<name>", type "DIRECT" or parameters [AUTO_DELETE] do not match the server.'
        );

        $this->subject->exchange(
            '<name>',
            ExchangeType::DIRECT()
        );
    }

    public function testExchangeWithOtherAmqpException()
    {
        // The AMQPProtocolChannelException constructor accesses global data
        // which is not initialized unless there is an actual connection which
        // makes it difficult to mock. Here, we bypass the constructor entirely.
        $exception = Phony::fullMock(AMQPProtocolChannelException::class, null)->mock();

        $this->channel->exchange_declare->throws($exception);

        $this->setExpectedException(
            AMQPProtocolChannelException::class
        );

        $this->subject->exchange(
            '<name>',
            ExchangeType::DIRECT()
        );
    }

    public function testExchangeWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testExchangeWithClosedChannel()
    {
        $this->subject->close();

        $this->setExpectedException(
            LogicException::class,
            'Channel is closed.'
        );

        $this->subject->exchange(
            '<name>',
            ExchangeType::DIRECT()
        );
    }

    public function testDirectExchange()
    {
        $this->assertEquals(
            new PalExchange(
                '',
                ExchangeType::DIRECT(),
                ExchangeParameter::normalize([ExchangeParameter::DURABLE()]),
                $this->channel->mock()
            ),
            $this->subject->directExchange()
        );
    }

    public function testDirectExchangeWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testDirectExchangeWithClosedChannel()
    {
        $this->subject->close();

        $this->setExpectedException(
            LogicException::class,
            'Channel is closed.'
        );

        $this->subject->directExchange();
    }

    /**
     * @dataProvider exchangeTypes
     */
    public function testAmqExchange(ExchangeType $type, $palType, $name)
    {
        $this->assertEquals(
            new PalExchange(
                $name,
                $type,
                ExchangeParameter::normalize([ExchangeParameter::DURABLE()]),
                $this->channel->mock()
            ),
            $this->subject->amqExchange($type)
        );
    }

    public function testAmqExchangeWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testAmqExchangeWithClosedChannel()
    {
        $this->subject->close();

        $this->setExpectedException(
            LogicException::class,
            'Channel is closed.'
        );

        $this->subject->amqExchange(
            ExchangeType::DIRECT()
        );
    }

    public function testQueue()
    {
        $result = $this->subject->queue();

        $this->channel->queue_declare->calledWith(
            '',
            false, // passive
            false, // durable
            true,  // exclusive
            true   // auto-delete
        );

        $this->assertEquals(
            new PalQueue(
                '<server-generated>',
                QueueParameter::normalize(null), // defaults
                $this->channel->mock()
            ),
            $result
        );
    }

    public function testQueueWithName()
    {
        $result = $this->subject->queue('<name>');

        $this->channel->queue_declare->calledWith(
            '<name>',
            false, // passive
            false, // durable
            true,  // exclusive
            true   // auto-delete
        );

        $this->assertEquals(
            new PalQueue(
                '<name>',
                QueueParameter::normalize(null), // defaults
                $this->channel->mock()
            ),
            $result
        );
    }

    public function testQueueWithParameters()
    {
        // Test with the inverse of the default properties to cover on/off states ...
        $properties = [
            QueueParameter::PASSIVE(),
            QueueParameter::DURABLE(),
        ];

        $result = $this->subject->queue(
            '<name>',
            $properties
        );

        $this->channel->queue_declare->calledWith(
            '<name>',
            true,  // passive
            true,  // durable
            false, // exclusive
            false  // auto-delete
        );

        $this->assertEquals(
            new PalQueue(
                '<name>',
                QueueParameter::normalize($properties),
                $this->channel->mock()
            ),
            $result
        );
    }

    public function testQueueWithDeclarationFailure()
    {
        // The AMQPProtocolChannelException constructor accesses global data
        // which is not initialized unless there is an actual connection which
        // makes it difficult to mock. Here, we bypass the constructor entirely
        // and set the mocked exception code using reflection.
        $exception = Phony::fullMock(AMQPProtocolChannelException::class, null)->mock();

        $reflector = new ReflectionProperty(AMQPProtocolChannelException::class, 'code');
        $reflector->setAccessible(true);
        $reflector->setValue($exception, AmqpConstant::PRECONDITION_FAILED);

        $this->channel->queue_declare->throws($exception);

        $this->setExpectedException(
            DeclareException::class,
            'Failed to declare queue "<name>", parameters [EXCLUSIVE, AUTO_DELETE] do not match the server.'
        );

        $this->subject->queue('<name>');
    }

    public function testQueueWithLockedResource()
    {
        // The AMQPProtocolChannelException constructor accesses global data
        // which is not initialized unless there is an actual connection which
        // makes it difficult to mock. Here, we bypass the constructor entirely
        // and set the mocked exception code using reflection.
        $exception = Phony::fullMock(AMQPProtocolChannelException::class, null)->mock();

        $reflector = new ReflectionProperty(AMQPProtocolChannelException::class, 'code');
        $reflector->setAccessible(true);
        $reflector->setValue($exception, AmqpConstant::RESOURCE_LOCKED);

        $this->channel->queue_declare->throws($exception);

        $this->setExpectedException(
            ResourceLockedException::class,
            'Failed to declare queue "<name>", another connection has exclusive access.'
        );

        $this->subject->queue('<name>');
    }

    public function testQueueWithOtherAmqpException()
    {
        // The AMQPProtocolChannelException constructor accesses global data
        // which is not initialized unless there is an actual connection which
        // makes it difficult to mock. Here, we bypass the constructor entirely.
        $exception = Phony::fullMock(AMQPProtocolChannelException::class, null)->mock();

        $this->channel->queue_declare->throws($exception);

        $this->setExpectedException(
            AMQPProtocolChannelException::class
        );

        $this->subject->queue('<name>');
    }

    public function testQueueWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testQueueWithClosedChannel()
    {
        $this->subject->close();

        $this->setExpectedException(
            LogicException::class,
            'Channel is closed.'
        );

        $this->subject->queue();
    }

    public function testClose()
    {
        $this->assertTrue(
            $this->subject->isOpen()
        );

        $this->subject->close();

        $this->channel->close->called();

        $this->assertFalse(
            $this->subject->isOpen()
        );
    }

    public function testCloseCanBeCalledRepeatedly()
    {
        $this->subject->close();
        $this->subject->close();

        $this->channel->close->once()->called();
    }
}
