<?php
namespace Skewd\Amqp;

/**
 * An AMQP exchange.
 *
 * An exchange is the primary target for message publication.
 */
interface Exchange
{
    /**
     * Get the name of the exchange.
     *
     * @return string The exchange name.
     */
    public function name();

    /**
     * Get the exchange type.
     *
     * @return ExchangeType The exchange type.
     */
    public function type();

    /**
     * Get the parameters used when the exchange was declared.
     *
     * @return SplObjectStorage<ExchangeParameter, boolean> A map of parameter to on/off state.
     */
    public function parameters();

    /**
     * Publish a message to this exchange.
     *
     * @param Message $message The message to publish.
     */
    public function publish(Message $message);
}
