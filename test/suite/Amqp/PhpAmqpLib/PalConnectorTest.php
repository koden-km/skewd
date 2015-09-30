<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Skewd\Amqp\Connection\ConnectionException;

class PalConnectorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->isolator = Phony::fullMock(Isolator::class);
        $this->connection = Phony::fullMock(AbstractConnection::class);

        $this->subject = PalConnector::create(
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
        $this->isolator->new->returns($this->connection->mock());

        $result = $this->subject->connect();

        $this->isolator->new->calledWith(
            AMQPStreamConnection::class,
            '<host>',
            1234,
            '<username>',
            '<password>',
            '<vhost>'
        );

        $this->assertEquals(
            new PalConnection(
                $this->connection->mock()
            ),
            $result
        );
    }

    public function testConnectFailure()
    {
        $exception = Phony::fullMock([AMQPExceptionInterface::class, Exception::class]);

        $this->isolator->new->throws($exception->mock());

        $this->setExpectedException(
            ConnectionException::class,
            'Unable to connect to AMQP server.'
        );

        $this->subject->connect();
    }
}
