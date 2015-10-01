<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Phony\Phpunit\Phony;
use LogicException;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Amqp\ExchangeType;
use Skewd\Amqp\Message;
use Skewd\Amqp\PublishOption;
use SplObjectStorage;

class PalExchangeTest extends PHPUnit_Framework_TestCase
{
    // use the trait so we don't have to retest message marshalling here ...
    use MessageMarshallerTrait;

    public function setUp()
    {
        $this->channel = Phony::fullMock(AMQPChannel::class);
        $this->parameters = new SplObjectStorage();

        $this->subject = new PalExchange(
            '<name>',
            ExchangeType::FANOUT(),
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

    public function testType()
    {
        $this->assertSame(
            ExchangeType::FANOUT(),
            $this->subject->type()
        );
    }

    public function testParameters()
    {
        $this->assertSame(
            $this->parameters,
            $this->subject->parameters()
        );
    }

    public function testPublish()
    {
        $message = Message::create('<payload>');

        $this->subject->publish($message);

        $this->channel->basic_publish->calledWith(
            $this->fromStandardMessage($message),
            '<name>', // exchange
            '',       // routing key
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
            '',
            $options
        );

        $this->channel->basic_publish->calledWith(
            $this->fromStandardMessage($message),
            '<name>', // exchange
            '',       // routing key
            true,     // mandatory
            true      // immediate
        );
    }

    public function testPublishWithRoutingKeyWhenNotRequired()
    {
        $this->setExpectedException(
            LogicException::class,
            'Routing key must be empty for FANOUT exchanges.'
        );

        $this->subject->publish(
            Message::create(''),
            '<routing-key>'
        );
    }

    public function testPublishWithoutRoutingKeyWhenRequired()
    {
        $this->subject = new PalExchange(
            '<name>',
            ExchangeType::DIRECT(),
            $this->parameters,
            $this->channel->mock()
        );

        $this->setExpectedException(
            LogicException::class,
            'Routing key must be provided for DIRECT exchanges.'
        );

        $this->subject->publish(
            Message::create('')
        );
    }

    public function testPublishWithDisconnection()
    {
        $this->markTestIncomplete();
    }
}
