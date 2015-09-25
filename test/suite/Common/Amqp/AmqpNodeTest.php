<?php
namespace Skewd\Common\Amqp;

use Eloquent\Phony\Phpunit\Phony;
use PHPUnit_Framework_TestCase;

class AmqpNodeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connector = Phony::fullMock(Connector::class);

        $this->subject = AmqpNode::create(
            $this->connector->mock()
        );
    }

    public function testX()
    {
        $this->markTestIncomplete();
    }
}
