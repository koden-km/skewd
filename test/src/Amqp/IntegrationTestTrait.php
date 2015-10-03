<?php
namespace Skewd\Amqp;

use Eloquent\Phony\Phony;
use Skewd\Amqp\Connection\ConnectionException;

trait IntegrationTestTrait
{
    public function setUp()
    {
        $this->connector = $this->createConnector();

        try {
            $this->connection = $this->connector->connect();
        } catch (ConnectionException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    abstract public function createConnector();

    public function testConsume()
    {
        $channel = $this->connection->channel();

        $message = Message::create('Hello, world!');

        $queue = $channel->queue('', [QueueParameter::EXCLUSIVE(), QueueParameter::AUTO_DELETE()]);
        $queue->publish($message);

        $callback = Phony::stub();
        $queue->consume($callback);
        $connection->wait(1);

        $consumerMessage = $callback->called()->argument(0);

        $this->assertInstanceOf(
            ConsumerMessage::class,
            $consumerMessage
        );

        $this->assertEquals(
            $message,
            $consumerMessage->message()
        );
    }
}
