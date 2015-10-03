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
        // create the queue ...
        $channel = $this->connection->channel();
        $queue = $channel->queue();

        // publish the message ...
        $message = Message::create('Hello, world!');
        $queue->publish($message);

        // enable the consumer ...
        $callback = Phony::stub();
        $queue->consume($callback);

        // wait for activity ...
        $this->connection->wait(0.5);

        // verify that the callback was invoked and capture the message ...
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
