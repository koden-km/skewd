<?php
namespace Skewd\Lock;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Skewd\Amqp\Channel;
use Skewd\Amqp\Connection\Connection;
use Skewd\Amqp\Consumer;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\Queue;
use Skewd\Amqp\QueueParameter;
use Skewd\Amqp\ResourceLockedException;
use Skewd\Amqp\ResourceNotFoundException;
use Skewd\Common\Node\Node;

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
