<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Phony\Phpunit\Phony;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\Exchange;
use Skewd\Amqp\ExchangeType;
use Skewd\Amqp\Message;
use Skewd\Amqp\PublishOption;
use SplObjectStorage;

class PalQueueTest extends PHPUnit_Framework_TestCase
{
    // use the trait so we don't have to retest message marshalling here ...
    use MessageMarshallerTrait;

    public function setUp()
    {
        $this->channel = Phony::fullMock(AMQPChannel::class);
        $this->channel->basic_consume->does(
            function ($queue, $tag) {
                if ($tag === '') {
                    return ['<server-generated>'];
                }

                return [$tag];
            }
        );

        $this->parameters = new SplObjectStorage();

        $this->exchange = Phony::fullMock(Exchange::class);
        $this->exchange->name->returns('<exchange>');
        $this->exchange->type->returns(ExchangeType::DIRECT());

        $this->subject = new PalQueue(
            '<name>',
            $this->parameters,
            $this->channel->mock()
        );
    }

    public function testName()
    {
        $this->assertSame(
            '<name>',
            $this->subject->name()
        );
    }

    public function testParameters()
    {
        $this->assertSame(
            $this->parameters,
            $this->subject->parameters()
        );
    }

    public function testBind()
    {
        $this->subject->bind(
            $this->exchange->mock(),
            '<routing-key>'
        );

        $this->channel->queue_bind->calledWith(
            '<name>',
            '<exchange>',
            '<routing-key>'
        );
    }

    public function testBindWithRoutingKeyWhenNotRequired()
    {
        $this->exchange->type->returns(ExchangeType::FANOUT());

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Routing key must be empty for FANOUT exchanges.'
        );

        $this->subject->bind(
            $this->exchange->mock(),
            '<routing-key>'
        );
    }

    public function testBindWithoutRoutingKeyWhenRequired()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'Routing key must be provided for DIRECT exchanges.'
        );

        $this->subject->bind(
            $this->exchange->mock()
        );
    }

    public function testBindWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testUnbind()
    {
        $this->subject->unbind(
            $this->exchange->mock(),
            '<routing-key>'
        );

        $this->channel->queue_unbind->calledWith(
            '<name>',
            '<exchange>',
            '<routing-key>'
        );
    }

    public function testUnbindWithRoutingKeyWhenNotRequired()
    {
        $this->exchange->type->returns(ExchangeType::FANOUT());

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Routing key must be empty for FANOUT exchanges.'
        );

        $this->subject->unbind(
            $this->exchange->mock(),
            '<routing-key>'
        );
    }

    public function testUnbindWithoutRoutingKeyWhenRequired()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'Routing key must be provided for DIRECT exchanges.'
        );

        $this->subject->unbind(
            $this->exchange->mock()
        );
    }

    public function testUnbindWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testPublish()
    {
        $message = Message::create('<payload>');

        $this->subject->publish($message);

        $this->channel->basic_publish->calledWith(
            $this->fromStandardMessage($message),
            '',       // exchange
            '<name>', // routing key
            false,    // mandatory
            false     // immediate
        );
    }

    public function testPublishWithOptions()
    {
        // Test with the inverse of the default options to cover on/off states ...
        $options = [
            PublishOption::MANDATORY(),
            PublishOption::IMMEDIATE(),
        ];

        $message = Message::create('<payload>');

        $this->subject->publish(
            $message,
            $options
        );

        $this->channel->basic_publish->calledWith(
            $this->fromStandardMessage($message),
            '',       // exchange
            '<name>', // routing key
            true,     // mandatory
            true      // immediate
        );
    }

    public function testPublishWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testConsume()
    {
        $callback = Phony::stub();

        $result = $this->subject->consume($callback);

        $call = $this->channel->basic_consume->calledWith(
            '<name>',
            '',    // tag
            false, // no-local
            false, // no-ack
            false, // exclusive
            false, // no-wait
            '~'    // message handler function
        );

        // capture the message handler function passed to basic_consume() ...
        $handler = $call->argument(6);

        $this->assertEquals(
            new PalConsumer(
                $this->subject,
                ConsumerParameter::normalize(null), // defailts
                '<server-generated>',
                $this->channel->mock()
            ),
            $result
        );

        $this->assertTrue(
            is_callable($handler)
        );

        $callback->never()->called();

        $message = new AMQPMessage();
        $handler($message);

        $callback->calledWith(
            $result,
            $this->toStandardMessage($message)
        );
    }

    public function testConsumeWithParameters()
    {
        // Test with the inverse of the default properties to cover on/off states ...
        $properties = [
            ConsumerParameter::NO_LOCAL(),
            ConsumerParameter::NO_ACK(),
            ConsumerParameter::EXCLUSIVE(),
        ];

        $this->subject->consume(
            function () {},
            $properties
        );

        $this->channel->basic_consume->calledWith(
            '<name>',
            '',    // tag
            true,  // no-local
            true,  // no-ack
            true,  // exclusive
            false, // no-wait
            '~'    // message handler function
        );
    }

    public function testConsumeWithExplicitTag()
    {
        $result = $this->subject->consume(
            function () {},
            null,
            '<tag>'
        );

        $this->channel->basic_consume->calledWith(
            '<name>',
            '<tag>',
            false, // no-local
            false, // no-ack
            false, // exclusive
            false, // no-wait
            '~'    // message handler function
        );

        $this->assertSame(
            '<tag>',
            $result->tag()
        );
    }

    public function testConsumeWithDisconnection()
    {
        $this->markTestIncomplete();
    }
}
