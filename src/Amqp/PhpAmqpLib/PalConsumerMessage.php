<?php
namespace Skewd\Amqp\PhpAmqpLib;

use LogicException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Skewd\Amqp\Consumer;
use Skewd\Amqp\ConsumerMessage;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\Message;
use Skewd\Amqp\Queue;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * A message delivered by a consumer.
 */
final class PalConsumerMessage implements ConsumerMessage
{
    /**
     * @param Consumer    $consumer
     * @param AMQPMessage $message
     * @param bool        $noAck
     * @param AMQPChannel $channel
     */
    public function __construct(
        Consumer $consumer,
        AMQPMessage $message,
        $noAck,
        AMQPChannel $channel
    ) {
        $this->consumer = $consumer;
        $this->message = $message;
        $this->noAck = $noAck;
        $this->channel = $channel;
    }

    /**
     * Get the consumer that the message was delivered to.
     *
     * @return Consumer The consumer.
     */
    public function consumer()
    {
        return $this->consumer;
    }

    /**
     * Get the AMQP message.
     *
     * @return Message The AMQP message.
     */
    public function message()
    {
        if (null === $this->standardMessage) {
            $this->standardMessage = $this->toStandardMessage(
                $this->message
            );
        }

        return $this->standardMessage;
    }

    /**
     * Get the delivery tag.
     *
     * @return string The delivery tag.
     */
    public function tag()
    {
        return $this->message->delivery_info['delivery_tag'];
    }

    /**
     * Check if the message has been redelivered.
     *
     * @return boolean True if the message has been redelivered; otherwise, false.
     */
    public function isRedelivered()
    {
        return (bool) $this->message->delivery_info['redelivered'];
    }

    /**
     * Get the name of the exchange that the message was published to.
     *
     * @return string The exchange name.
     */
    public function exchange()
    {
        return $this->message->delivery_info['exchange'];
    }

    /**
     * Get the routing key used when the message was published.
     *
     * @return string The routing key.
     */
    public function routingKey()
    {
        return $this->message->delivery_info['routing_key'];
    }

    /**
     * Acknowledge the message.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if this consumer is using ConsumerParameter::NO_ACK.
     */
    public function ack()
    {
        if ($this->noAck) {
            throw new LogicException(
                'Can not acknowledge message, consumer uses NO_ACK parameter.'
            );
        }

        $this->channel->basic_ack(
            $this->tag()
        );
    }

    /**
     * Reject the message and requeue it.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function reject()
    {
        $this->channel->basic_reject(
            $this->tag(),
            true
        );
    }

    /**
     * Reject the message without requeing it.
     *
     * The server implementation may discard the message outright, or deliver it
     * to a dead-letter queue, depending on configuration.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function discard()
    {
        $this->channel->basic_reject(
            $this->tag(),
            false
        );
    }

    use MessageMarshallerTrait;

    private $consumer;
    private $message;
    private $standardMessage;
    private $noAck;
    private $channel;
}
