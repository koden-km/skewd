<?php
namespace Skewd\Amqp\Connection;

use Exception;
use PHPUnit_Framework_TestCase;

class ConnectionExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testCouldNotConnect()
    {
        $previous = new Exception();
        $exception = ConnectionException::couldNotConnect($previous);

        $this->assertSame(
            'Unable to connect to AMQP server.',
            $exception->getMessage()
        );

        $this->assertSame(
            $previous,
            $exception->getPrevious()
        );
    }

    public function testNotConnected()
    {
        $previous = new Exception();
        $exception = ConnectionException::notConnected($previous);

        $this->assertSame(
            'Disconnected from AMQP server.',
            $exception->getMessage()
        );

        $this->assertSame(
            $previous,
            $exception->getPrevious()
        );
    }
}
