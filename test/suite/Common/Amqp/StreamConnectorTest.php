<?php
namespace Skewd\Common\Amqp;

use Eloquent\Phony\Phpunit\Phony;
use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AbstractConnection;

class StreamConnectorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestSkipped();

        $this->connection = Phony::fullMock(AbstractConnection::class);

        $this->isolator = Phony::fullMock(Isolator::class);
        $this->isolator->new->returns($this->connection->mock());

        $this->subject = StreamConnector::create(
            '<host>',
            1234,
            '<username>',
            '<password>',
            '<vhost>'
        );

        $this->subject->setIsolator($this->isolator->mock());
    }

    public function testConnect()
    {
        $result = $this->subject->connect();

        $this->isolator->new->calledWith(
            AMQPStreamConnection::class,
            '<host>',
            1234,
            '<username>',
            '<password>',
            '<vhost>'
        );

        $this->assertSame(
            $this->connection->mock(),
            $result
        );
    }
}
