<?php
namespace Skewd\Amqp;

/**
 * An AMQP queue.
 *
 * Messages are delivered to queues and are then read by consumers. A queue
 * receives messages by creating bindings to one or more exchanges.
 */
interface Queue
{
    /**
     * Get the name of the queue.
     *
     * @return string The queue name.
     */
    public function name();

    /**
     * Get the queue parameters.
     *
     * @return SplObjectStorage<QueueParameter, boolean> A map of parameter to on/off state.
     */
    public function parameters();

    /**
     * Bind this queue to an exchange.
     *
     * @param Exchange $exchange   The exchange.
     * @param string   $routingKey The routing key for DIRECT and TOPIC exchanges, or empty string for FANOUT and HEADERS exchanges.
     *
     * @throws ConnectionException      if not connected to the AMQP server.
     * @throws InvalidArgumentException if a routing key is required but not provided, and vice-versa.
     */
    public function bind(Exchange $exchange, $routingKey = '');

    /**
     * Unbind this queue from an exchange.
     *
     * @param Exchange $exchange   The exchange.
     * @param string   $routingKey The routing key for DIRECT and TOPIC exchanges, or empty string for FANOUT and HEADERS exchanges.
     *
     * @throws ConnectionException      if not connected to the AMQP server.
     * @throws InvalidArgumentException if a routing key is required but not provided, and vice-versa.
     */
    public function unbind(Exchange $exchange, $routingKey = '');

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
    public function publish(Message $message, array $options = null);

    /**
     * Consume messages from this queue.
     *
     * Invokes a callback when a message is received from this queue.
     *
     * The callback signature is $callback(ConsumerMessage $message).
     *
     * @param callable                      $callback   The callback to invoke when a message is received.
     * @param array<ConsumerParameter>|null $parameters Parameters to set on the consumer, or null to use the defaults.
     * @param string                        $tag        A unique identifier for the consumer, or an empty string to have the server generate the consumer tag.
     *
     * @return Consumer
     * @throws ResourceLockedException   if another connection has an exclusive consumer.
     * @throws ResourceNotFoundException if the queue does not exist on the server.
     * @throws ConnectionException       if not connected to the AMQP server.
     */
    public function consume(
        callable $callback,
        array $parameters = null,
        $tag = ''
    );
}
