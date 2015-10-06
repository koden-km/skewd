<?php
namespace Skewd\Amqp\PhpAmqpLib;

use LogicException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Skewd\Amqp\Channel;
use Skewd\Amqp\DeclareException;
use Skewd\Amqp\ExchangeParameter;
use Skewd\Amqp\ExchangeType;
use Skewd\Amqp\QueueParameter;
use Skewd\Amqp\ResourceLockedException;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * An AMQP channel.
 */
final class PalChannel implements Channel
{
    /**
     * @param AMQPChannel $channel
     */
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Declare an exchange.
     *
     * To get a reference to one of the pre-declared exchanges, see the methods
     * below:
     *
     * @see Channel::directExchange()
     * @see Channel::amqExchange()
     *
     * @param string                        $name       The exchange name.
     * @param ExchangeType                  $type       The exchange type.
     * @param array<ExchangeParameter>|null $parameters Parameters to set on the exchange, or null to use the defaults.
     *
     * @return Exchange            The exchange.
     * @throws DeclareException    if the exchange could not be declared because it already exists with different parameters.
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if the channel has been closed.
     */
    public function exchange($name, ExchangeType $type, array $parameters = null)
    {
        if (!$this->channel) {
            throw new LogicException('Channel is closed.');
        }

        $parameters = ExchangeParameter::normalize($parameters);

        try {
            $this->channel->exchange_declare(
                $name,
                $type->value(),
                $parameters[ExchangeParameter::PASSIVE()],
                $parameters[ExchangeParameter::DURABLE()],
                $parameters[ExchangeParameter::AUTO_DELETE()],
                $parameters[ExchangeParameter::INTERNAL()]
            );
        } catch (AMQPProtocolChannelException $e) {
            if (AmqpConstant::PRECONDITION_FAILED === $e->getCode()) {
                throw DeclareException::exchangeTypeOrParameterMismatch(
                    $name,
                    $type,
                    $parameters,
                    $e
                );
            }

            throw $e;
        }

        return new PalExchange(
            $name,
            $type,
            $parameters,
            $this->channel,
            $this
        );
    }

    /**
     * Get the pre-declared, nameless, direct exchange.
     *
     * Every queue is automatically bound to the default exchange with a routing
     * key the same as the queue name.
     *
     * @link https://www.rabbitmq.com/tutorials/amqp-concepts.html#exchanges
     *
     * @see Channel::exchange()
     * @see Channel::amqExchange()
     *
     * @return Exchange            The pre-declared, nameless, direct exchange.
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if the channel has been closed.
     */
    public function directExchange()
    {
        if (!$this->channel) {
            throw new LogicException('Channel is closed.');
        }

        return new PalExchange(
            '',
            ExchangeType::DIRECT(),
            ExchangeParameter::normalize([ExchangeParameter::DURABLE()]),
            $this->channel,
            $this
        );
    }

    /**
     * Get the pre-declared amq.* exchange of the given type.
     *
     * @link https://www.rabbitmq.com/tutorials/amqp-concepts.html#exchanges
     *
     * @see Channel::exchange()
     * @see Channel::directExchange()
     *
     * @param ExchangeType $type The exchange type.
     *
     * @return Exchange            The amq.* exchange.
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if the channel has been closed.
     */
    public function amqExchange(ExchangeType $type)
    {
        if (!$this->channel) {
            throw new LogicException('Channel is closed.');
        }

        return new PalExchange(
            'amq.' . $type->value(),
            $type,
            ExchangeParameter::normalize([ExchangeParameter::DURABLE()]),
            $this->channel,
            $this
        );
    }

    /**
     * Declare a queue.
     *
     * @param string                     $name       The queue name, or an empty string to have the server generated a name.
     * @param array<QueueParameter>|null $parameters Parameters to set on the queue, or null to use the defaults.
     *
     * @return Queue                   The queue.
     * @throws DeclareException        if the queue could not be declared because it already exists with different parameters.
     * @throws ResourceLockedException if the queue already exists, but another connection has exclusive access.
     * @throws ConnectionException     if not connected to the AMQP server.
     * @throws LogicException          if the channel has been closed.
     */
    public function queue($name = '', array $parameters = null)
    {
        if (!$this->channel) {
            throw new LogicException('Channel is closed.');
        }

        $parameters = QueueParameter::normalize($parameters);

        try {
            list($name) = $this->channel->queue_declare(
                $name,
                $parameters[QueueParameter::PASSIVE()],
                $parameters[QueueParameter::DURABLE()],
                $parameters[QueueParameter::EXCLUSIVE()],
                $parameters[QueueParameter::AUTO_DELETE()]
            );
        } catch (AMQPProtocolChannelException $e) {
            if (AmqpConstant::PRECONDITION_FAILED === $e->getCode()) {
                throw DeclareException::queueParameterMismatch(
                    $name,
                    $parameters,
                    $e
                );
            } elseif (AmqpConstant::RESOURCE_LOCKED === $e->getCode()) {
                throw ResourceLockedException::queueIsExclusive(
                    $name,
                    $e
                );
            }

            throw $e;
        }

        return new PalQueue(
            $name,
            $parameters,
            $this->channel,
            $this
        );
    }

    /**
     * Set the channel's Quality-of-Service parameters.
     *
     * @param integer|null $count The number of un-acknowledged messages to accept, or null for no limit.
     * @param integer|null $size  The total size of un-acknowledged messages to accept, in bytes, or null for no limit.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if the channel has been closed.
     */
    public function qos($count = null, $size = null)
    {
        if (!$this->channel) {
            throw new LogicException('Channel is closed.');
        }

        $this->channel->basic_qos(
            $size ?: 0,
            $count ?: 0,
            false
        );
    }

    /**
     * Check if the channel is still open.
     *
     * @return boolean True if the channel is open; otherwise, false.
     */
    public function isOpen()
    {
        return null !== $this->channel;
    }

    /**
     * Close the channel.
     *
     * Closing a channel invalidates all exchanges and queues objects created via
     * this channel instance. It does not necessarily remove those resources on
     * the server.
     */
    public function close()
    {
        if (!$this->channel) {
            return;
        }

        try {
            $this->channel->close();
        } finally {
            $this->channel = null;
        }
    }

    private $channel;
}
