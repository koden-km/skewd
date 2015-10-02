<?php
namespace Skewd\Amqp;

/**
 * A message delivered by a consumer.
 *
 * Passed to consumer callbacks when a message arrives.
 */
interface ConsumerMessage
{
    /**
     * Get the consumer that the message was delivered to.
     *
     * @return Consumer The consumer.
     */
    public function consumer();

    /**
     * Get the AMQP message.
     *
     * @return Message The AMQP message.
     */
    public function message();

    /**
     * Get the delivery tag.
     *
     * @return string The delivery tag.
     */
    public function tag();

    /**
     * Check if the message has been redelivered.
     *
     * @return boolean True if the message has been redelivered; otherwise, false.
     */
    public function isRedelivered();

    /**
     * Get the name of the exchange that the message was published to.
     *
     * @return string The exchange name.
     */
    public function exchange();

    /**
     * Get the routing key used when the message was published.
     *
     * @return string The routing key.
     */
    public function routingKey();

    /**
     * Acknowledge the message.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if this consumer is using ConsumerParameter::NO_ACK.
     */
    public function ack();

    /**
     * Reject the message and requeue it.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function reject();

    /**
     * Reject the message without requeing it.
     *
     * The server implementation may discard the message outright, or deliver it
     * to a dead-letter queue, depending on configuration.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function discard();
}
