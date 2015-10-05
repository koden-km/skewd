<?php
namespace Skewd\Lock;

use Eloquent\Phony\Phpunit\Phony;
use PHPUnit_Framework_TestCase;
use Skewd\Amqp\Connection\Connection;

class AmqpResourceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phony::fullMock(Connection::class);

        $this->subject = AmqpResourceFactory::create(
            $this->connection->mock()
        );
    }

    public function testCreateResource()
    {
        $this->assertEquals(
            new AmqpResource(
                $this->connection->mock(),
                '<name>'
            ),
            $this->subject->createResource('<name>')
        );
    }
}
