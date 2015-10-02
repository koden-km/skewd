<?php
namespace Skewd\Amqp\PhpAmqpLib;

use LogicException;
use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Amqp\Consumer;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\Message;
use Skewd\Amqp\Queue;
use SplObjectStorage;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * A message consumer.
 */
final class PalConsumer implements Consumer
{
    public function __construct(
        Queue $queue,
        SplObjectStorage $parameters,
        $tag,
        AMQPChannel $channel
    ) {
        $this->queue = $queue;
        $this->parameters = $parameters;
        $this->tag = $tag;
        $this->channel = $channel;
        $this->noAck = $this->parameters[ConsumerParameter::NO_ACK()];
    }

    /**
     * Get the queue from which messages are consumed.
     *
     * @return Queue
     */
    public function queue()
    {
        return $this->queue;
    }

    /**
     * Get the consumer parameters.
     *
     * @return SplObjectStorage<ConsumerParameter, boolean> A map of parameter to on/off state.
     */
    public function parameters()
    {
        return $this->parameters;
    }

    /**
     * Get the consumer tag.
     *
     * @return string The consumer tag.
     */
    public function tag()
    {
        return $this->tag;
    }

    /**
     * Set the consumer tag.
     *
     * Note that this method is not part of the Consumer interface, it is
     * present to allow server-generated tags to bet set after the consumer is
     * constructed.
     *
     * @return string The consumer tag.
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Acknowledge a message.
     *
     * @param Message $message
     *
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if the message was not delivered via this consumer.
     * @throws LogicException      if this consumer is using ConsumerParameter::NO_ACK.
     */
    public function ack(Message $message)
    {
        if ($this->noAck) {
            throw new LogicException(
                'Can not acknowledge message, consumer has NO_ACK property.'
            );
        }

        $this->channel->basic_ack(
            $message->amqpProperties()->get('delivery_tag')
        );
    }

    /**
     * Reject a message.
     *
     * @param Message $message
     * @param boolean $requeue True to place the message back on the queue; otherwise, false.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function reject(Message $message, $requeue = true)
    {
        $this->channel->basic_reject(
            $message->amqpProperties()->get('delivery_tag'),
            $requeue
        );
    }

    /**
     * Stop consuming messages.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function cancel()
    {
        $this->channel->basic_cancel($this->tag);
    }

    private $queue;
    private $parameters;
    private $tag;
    private $channel;
    private $noAck;
}
