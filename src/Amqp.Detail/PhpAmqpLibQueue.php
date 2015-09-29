<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Skewd\Amqp\Queue;
use Skewd\Amqp\QueueParameter;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * An AMQP queue.
 */
final class PhpAmqpLibQueue implements Queue
{
    public function __construct($name, array $parameters = null, AMQPChannel $channel)
    {
        $this->name = $name;
        $this->parameters = QueueParameter::adapt($parameters);
        $this->channel = $channel;
    }

    /**
     * Get the name of the queue.
     *
     * @return string The queue name.
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get the queue parameters.
     *
     * @return array<QueueParameter> The queue parameters.
     */
    public function parameters()
    {
        return $this->parameters;
    }

    /**
     * Bind this queue to an exchange.
     *
     * @param Exchange    $exchange   The exchange.
     * @param string|null $routingKey The routing key, or null if the exchange type is FANOUT or HEADERS.
     */
    public function bind(Exchange $exchange, $routingKey = null)
    {
        $this->channel->queue_bind(
            $this->name,
            $exchange->name(),
            $routingKey
        );
    }

    /**
     * Consume messages from this queue.
     *
     * @param array<ConsumerParameter>|null $parameters Parameters to set on the consumer, or null to use the defaults.
     * @param string                        $tag        A unique identifier for the consumer, or an empty string to have the server generated the consumer tag.
     *
     * @return Consumer
     */
    public function consume(array $parameters = null, $tag = '')
    {
        $parameters = ConsumerParameter::adapt($parameters);
        $consumer = null;

        list($tag) = $this->channel->basic_consume(
            $this->name,
            $tag,
            in_array(ConsumerParameter::NO_LOCAL(), $parameters, true),
            in_array(ConsumerParameter::NO_ACK(), $parameters, true),
            in_array(ConsumerParameter::EXCLUSIVE(), $parameters, true),
            false,
            function (AMQPMessage $message) use (&$consumer) {
                $consumer->push(
                    $this->toStandardMessage($message)
                );
            }
        );

        // Assign to $consumer as it's captured by the closure above ...
        $consumer = new PhpAmqpLibConsumer(
            $parameters,
            $tag,
            $this->channel
        );

        return $consumer;
    }

    use MessageMarshallerTrait;

    private $name;
    private $parameters;
    private $channel;
}
