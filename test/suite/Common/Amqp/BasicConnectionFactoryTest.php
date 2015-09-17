<?php
namespace Skewd\Common\Amqp;

use Eloquent\Phony\Phpunit\Phony;
use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class BasicConnectionFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->isolator = Phony::fullMock(Isolator::class);

        $this->subject = new BasicConnectionFactory(
            '<host>',
            1234,
            '<username>',
            '<password>',
            '<vhost>'
        );

        $this->subject->setIsolator($this->isolator->mock());
    }

    public function testCreate()
    {
        $this->isolator->new->returns('<instance>');

        $result = $this->subject->create();

        $this->isolator->new->calledWith(
            AMQPStreamConnection::class,
            '<host>',
            1234,
            '<username>',
            '<password>',
            '<vhost>'
        );

        $this->assertEquals(
            '<instance>',
            $result
        );
    }
}
