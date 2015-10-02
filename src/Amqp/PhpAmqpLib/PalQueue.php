<?php
namespace Skewd\Amqp\PhpAmqpLib;

use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\Exchange;
use Skewd\Amqp\Message;
use Skewd\Amqp\PublishOption;
use Skewd\Amqp\Queue;
use Skewd\Amqp\QueueParameter;
use SplObjectStorage;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * An AMQP queue.
 */
final class PalQueue implements Queue
{
    public function __construct(
        $name,
        SplObjectStorage $parameters,
        AMQPChannel $channel
    ) {
        $this->name = $name;
        $this->parameters = $parameters;
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
     * @return SplObjectStorage<QueueParameter, boolean> A map of parameter to on/off state.
     */
    public function parameters()
    {
        return $this->parameters;
    }

    /**
     * Bind this queue to an exchange.
     *
     * @param Exchange $exchange   The exchange.
     * @param string   $routingKey The routing key for DIRECT and TOPIC exchanges, or empty string for FANOUT and HEADERS exchanges.
     *
     * @throws ConnectionException      if not connected to the AMQP server.
     * @throws InvalidArgumentException if a routing key is required but not provided, and vice-versa.
     */
    public function bind(Exchange $exchange, $routingKey = '')
    {
        if ($exchange->type()->requiresRoutingKey()) {
            if ('' === $routingKey) {
                throw new InvalidArgumentException(
                    'Routing key must be provided for ' . $exchange->type()->key() . ' exchanges.'
                );
            }
        } elseif ('' !== $routingKey) {
            throw new InvalidArgumentException(
                'Routing key must be empty for ' . $exchange->type()->key() . ' exchanges.'
            );
        }

        $this->channel->queue_bind(
            $this->name,
            $exchange->name(),
            $routingKey
        );
    }

    /**
     * Unbind this queue from an exchange.
     *
     * @param Exchange $exchange   The exchange.
     * @param string   $routingKey The routing key for DIRECT and TOPIC exchanges, or empty string for FANOUT and HEADERS exchanges.
     *
     * @throws ConnectionException      if not connected to the AMQP server.
     * @throws InvalidArgumentException if a routing key is required but not provided, and vice-versa.
     */
    public function unbind(Exchange $exchange, $routingKey = '')
    {
        if ($exchange->type()->requiresRoutingKey()) {
            if ('' === $routingKey) {
                throw new InvalidArgumentException(
                    'Routing key must be provided for ' . $exchange->type()->key() . ' exchanges.'
                );
            }
        } elseif ('' !== $routingKey) {
            throw new InvalidArgumentException(
                'Routing key must be empty for ' . $exchange->type()->key() . ' exchanges.'
            );
        }

        $this->channel->queue_unbind(
            $this->name,
            $exchange->name(),
            $routingKey
        );
    }

    /**
     * Publish a message directly to this queue.
     *
     * This is a convenience method equivalent to publishing to the pre-declared,
     * nameless, direct exchange with a routing key equal to the queue name.
     *
     * @param Message                   $message The message to publish.
     * @param array<PublishOption>|null $options An array of options to set, or null to use the defaults.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function publish(Message $message, array $options = null)
    {
        $options = PublishOption::normalize($options);

        $this->channel->basic_publish(
            $this->fromStandardMessage($message),
            '',
            $this->name,
            $options[PublishOption::MANDATORY()],
            $options[PublishOption::IMMEDIATE()]
        );
    }

    /**
     * Consume messages from this queue.
     *
     * Invokes a callback when a message is received from this queue.
     *
     * The callback signature is $callback(Consumer $consumer, Message $message).
     *
     * @param callable                      $callback   The callback to invoke when a message is received.
     * @param array<ConsumerParameter>|null $parameters Parameters to set on the consumer, or null to use the defaults.
     * @param string                        $tag        A unique identifier for the consumer, or an empty string to have the server generate the consumer tag.
     *
     * @return Consumer
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function consume(
        callable $callback,
        array $parameters = null,
        $tag = ''
    ) {
        $parameters = ConsumerParameter::normalize($parameters);

        $consumer = new PalConsumer(
            $this,
            $parameters,
            $tag,
            $this->channel
        );

        list($tag) = $this->channel->basic_consume(
            $this->name,
            $tag,
            $parameters[ConsumerParameter::NO_LOCAL()],
            $parameters[ConsumerParameter::NO_ACK()],
            $parameters[ConsumerParameter::EXCLUSIVE()],
            false, // no-wait
            function (AMQPMessage $message) use ($consumer, $callback) {
                $callback(
                    $consumer,
                    $this->toStandardMessage($message)
                );
            }
        );

        $consumer->setTag($tag);

        return $consumer;
    }

    use MessageMarshallerTrait;

    private $name;
    private $parameters;
    private $channel;
}
