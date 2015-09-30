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

class PalConnectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phony::fullMock(AbstractConnection::class);
        $this->connection->isConnected->returns(true);

        $this->subject = new PalConnection(
            $this->connection->mock()
        );
    }

    public function testIsConnected()
    {
        $this->assertTrue(
            $this->subject->isConnected()
        );

        $this->connection->isConnected->returns(false);

        $this->assertFalse(
            $this->subject->isConnected()
        );
    }

    public function testClose()
    {
        $this->subject->close();

        $this->connection->close->called();

        $this->assertFalse(
            $this->subject->isConnected()
        );
    }

    public function testCloseCanBeCalledRepeatedly()
    {
        $this->subject->close();
        $this->subject->close();

        $this->connection->close->once()->called();
    }
}
