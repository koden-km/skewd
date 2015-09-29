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
     * To get a reference to one of the built-in exchanges, see the methods
     * below:
     *
     * @see Channel::defaultExchange()
     * @see Channel::builtInExchange()
     *
     * @param string                        $name       The exchange name.
     * @param ExchangeType                  $type       The exchange type.
     * @param array<ExchangeParameter>|null $parameters Parameters to set on the exchange, or null to use the defaults.
     *
     * @return Exchange         The exchange.
     * @throws DeclareException if the exchange could not be declared because it already exists with different parameters.
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function exchange($name, ExchangeType $type, array $parameters = null);

    /**
     * Get the built-in nameless direct exchange.
     *
     * Every queue is automatically bound to the default exchange with a routing
     * key the same as the queue name.
     *
     * @see Channel::exchange()
     * @see Channel::builtInExchange()
     *
     * @return Exchange The built-in nameless exchange.
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function defaultExchange();

    /**
     * Get the built-in amq.* exchange of the given type.
     *
     * @see Channel::exchange()
     * @see Channel::defaultExchange()
     *
     * @param ExchangeType $type The exchange type.
     *
     * @return Exchange The built-in exchange.
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function builtInExchange(ExchangeType $type);

    /**
     * Declare a queue.
     *
     * @param string                     $name       The queue name, or an empty string to have the server generated a name.
     * @param array<QueueParameter>|null $parameters Parameters to set on the queue, or null to use the defaults.
     *
     * @return Queue            The queue.
     * @throws DeclareException if the queue could not be declared because it already exists with different parameters.
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function queue($name = '', array $parameters = null);

    /**
     * Close the channel.
     *
     * Closing a channel invalides all exchanges and queues objects created via
     * this channel instance. It does not necessarily remove those resources on
     * the server.
     */
    public function close();
}
