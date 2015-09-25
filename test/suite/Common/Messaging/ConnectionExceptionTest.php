<?php
namespace Skewd\Common\Messaging;

use Exception;
use PHPUnit_Framework_TestCase;

class ConnectionExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception();
        $exception = ConnectionException::create($previous);

        $this->assertSame(
            'Unable to establish AMQP connection.',
            $exception->getMessage()
        );

        $this->assertSame(
            $previous,
            $exception->getPrevious()
        );
    }
}
