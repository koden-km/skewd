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
     * @param Exchange    $exchange   The exchange.
     * @param string|null $routingKey The routing key, or null if the exchange type is FANOUT or HEADERS.
     */
    public function bind(Exchange $exchange, $routingKey = null);

    /**
     * Unbind this queue from an exchange.
     *
     * @param Exchange    $exchange   The exchange.
     * @param string|null $routingKey The routing key, or null if the exchange type is FANOUT or HEADERS.
     */
    public function unbind(Exchange $exchange, $routingKey = null);

    /**
     * Publish a message directly to this queue.
     *
     * This is a convenience method equivalent to publishing to the pre-declared,
     * nameless, direct exchange with a routing key equal to the queue name.
     *
     * @param Message $message The message to publish.
     */
    public function publish(Message $message);

    /**
     * Consume messages from this queue.
     *
     * @param array<ConsumerParameter>|null $parameters Parameters to set on the consumer, or null to use the defaults.
     * @param string                        $tag        A unique identifier for the consumer, or an empty string to have the server generate the consumer tag.
     *
     * @return Consumer
     */
    public function consume(array $parameters = null, $tag = '');
}
