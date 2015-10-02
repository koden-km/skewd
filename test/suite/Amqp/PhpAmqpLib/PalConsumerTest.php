<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Phony\Phpunit\Phony;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\Message;
use Skewd\Amqp\Queue;

class PalConsumerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->queue = Phony::fullMock(Queue::class);
        $this->parameters = ConsumerParameter::normalize(null);
        $this->channel = Phony::fullMock(AMQPChannel::class);
        $this->message = Message::create(
            '',
            ['delivery_tag' => '<delivery-tag>']
        );

        $this->subject = new PalConsumer(
            $this->queue->mock(),
            $this->parameters,
            '<tag>',
            $this->channel->mock()
        );
    }

    public function testQueue()
    {
        $this->assertSame(
            $this->queue->mock(),
            $this->subject->queue()
        );
    }

    public function testParameters()
    {
        $this->assertSame(
            $this->parameters,
            $this->subject->parameters()
        );
    }

    public function testTag()
    {
        $this->assertSame(
            '<tag>',
            $this->subject->tag()
        );
    }

    public function testCancel()
    {
        $this->subject->cancel();

        $this->channel->basic_cancel->calledWith('<tag>');
    }

    public function testCancelWithDisconnection()
    {
        $this->markTestIncomplete();
    }
}
