<?php
namespace Skewd\Amqp;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * The type of an exchange.
 *
 * @link https://www.rabbitmq.com/tutorials/amqp-concepts.html#exchanges
 */
final class ExchangeType extends AbstractEnumeration
{
    /**
     * Route messages to all bindings with a routing key that matches exactly
     * the routing key of the message.
     */
    const DIRECT = 'direct';

    /**
     * Route messages to all bindings. The routing key is ignored.
     */
    const FANOUT = 'fanout';

    /**
     * Route messages to all bindings with a routing key whose pattern matches
     * the routing key of the messages.
     */
    const TOPIC = 'topic';

    /**
     * Route messages to all bindings with headers that match the message
     * headers. The routing key is ignored.
     */
    const HEADERS = 'headers';

    /**
     * Check whether this exchange type requires a routing key when publishing
     * a message.
     *
     * @return boolean True if the routing key is required; otherwise, false.
     */
    public function requiresRoutingKey()
    {
        return $this->anyOf(
            self::DIRECT(),
            self::TOPIC()
        );
    }
}
