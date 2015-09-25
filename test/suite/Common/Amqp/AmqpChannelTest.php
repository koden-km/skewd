<?php
namespace Skewd\Common\Amqp;

use Eloquent\Phony\Phpunit\Phony;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel as AMQPChannelImpl;

class AmqpChannelTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->impl = Phony::fullMock(AMQPChannelImpl::class);

        $this->subject = new AmqpChannel(
            $this->impl->mock()
        );
    }

    public function testX()
    {
        $this->markTestIncomplete();
    }
}
