<?php
namespace Skewd\Amqp;

/**
 * An AMQP channel.
 *
 * A channel is a "virtual" AMQP connection. All communication with the server
 * is carried out over one or more channels.
 */
interface Channel
{
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
    public function exchange($name, ExchangeType $type, array $parameters = null);

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
    public function directExchange();

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
    public function amqExchange(ExchangeType $type);

    /**
     * Declare a queue.
     *
     * Called with no arguments, this method will return an exclusive,
     * auto-deleting queue with a server-generated name.
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
    public function queue($name = '', array $parameters = null);

    /**
     * Set the channel's Quality-of-Service parameters.
     *
     * @param integer|null $count The maximum number of un-acknowledged messages to accept.
     * @param integer|null $size  The maximum size of un-acknowledged messages to accept, in bytes.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if the channel has been closed.
     */
    public function qos($count = null, $size = null);

    /**
     * Check if the channel is still open.
     *
     * @return boolean True if the channel is open; otherwise, false.
     */
    public function isOpen();

    /**
     * Close the channel.
     *
     * Closing a channel invalidates all exchanges and queues objects created via
     * this channel instance. It does not necessarily remove those resources on
     * the server.
     */
    public function close();
}
