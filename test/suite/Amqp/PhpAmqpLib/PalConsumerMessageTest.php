<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Phony\Phpunit\Phony;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Skewd\Amqp\Consumer;
use Skewd\Amqp\Exchange;
use Skewd\Amqp\Message;

class PalConsumerMessageTest extends PHPUnit_Framework_TestCase
{
    // use the trait so we don't have to retest message marshalling here ...
    use MessageMarshallerTrait;

    public function setUp()
    {
        $this->consumer = Phony::fullMock(Consumer::class);
        $this->message = new AMQPMessage();
        $this->channel = Phony::fullMock(AMQPChannel::class);

        $this->message->delivery_info = [
            'channel'      => $this->channel->mock(),
            'consumer_tag' => '<consumer-tag>',
            'delivery_tag' => '<delivery-tag>',
            'redelivered'  => false,
            'exchange'     => '<exchange>',
            'routing_key'  => '<routing-key>',
        ];

        $this->subject = new PalConsumerMessage(
            $this->consumer->mock(),
            $this->message,
            false, // no-ack
            $this->channel->mock()
        );
    }

    public function testConsumer()
    {
        $this->assertSame(
            $this->consumer->mock(),
            $this->subject->consumer()
        );
    }

    public function testMessage()
    {
        $this->assertEquals(
            $this->toStandardMessage($this->message),
            $this->subject->message()
        );
    }

    public function testTag()
    {
        $this->assertSame(
            '<delivery-tag>',
            $this->subject->tag()
        );
    }

    public function testIsRedelivered()
    {
        $this->assertFalse(
            $this->subject->isRedelivered()
        );

        $this->message->delivery_info['redelivered'] = true;

        $this->assertTrue(
            $this->subject->isRedelivered()
        );
    }

    public function testExchange()
    {
        $this->assertSame(
            '<exchange>',
            $this->subject->exchange()
        );
    }

    public function testRoutingKey()
    {
        $this->assertSame(
            '<routing-key>',
            $this->subject->routingKey()
        );
    }

    public function testAck()
    {
        $this->subject->ack();

        $this->channel->basic_ack->calledWith('<delivery-tag>');
    }

    public function testAckWithNoAckParameter()
    {
        $this->subject = new PalConsumerMessage(
            $this->consumer->mock(),
            $this->message,
            true, // no-ack
            $this->channel->mock()
        );

        $this->setExpectedException(
            'LogicException',
            'Can not acknowledge message, consumer uses NO_ACK parameter.'
        );

        $this->subject->ack($this->message);
    }

    public function testAckWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testReject()
    {
        $this->subject->reject();

        $this->channel->basic_reject->calledWith('<delivery-tag>', true);
    }

    public function testRejectWithDisconnection()
    {
        $this->markTestIncomplete();
    }

    public function testDiscard()
    {
        $this->subject->discard();

        $this->channel->basic_reject->calledWith('<delivery-tag>', false);
    }

    public function testDiscardWithDisconnection()
    {
        $this->markTestIncomplete();
    }
}
