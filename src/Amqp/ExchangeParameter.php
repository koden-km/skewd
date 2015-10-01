<?php
namespace Skewd\Amqp;

use Eloquent\Enumeration\AbstractEnumeration;
use InvalidArgumentException;
use SplObjectStorage;

/**
 * Parameters used to configure an exchange.
 */
final class ExchangeParameter extends AbstractEnumeration
{
    /**
     * Confirm that the exchange exists and matches the configured parameters,
     * but do not create it.
     */
    const PASSIVE = 'passive';

    /**
     * Persist the exchange across server restarts.
     */
    const DURABLE = 'durable';

    /**
     * Delete the exchange once there are no remaining queues bound to it.
     */
    const AUTO_DELETE = 'auto_delete';

    /**
     * Mark the exchange as internal. No messages can be published directly to
     * an internal exchange, rather it is the target for exchange-to-exchange
     * bindings.
     */
    const INTERNAL = 'internal';

    /**
     * Normalize an array of parameters.
     *
     * Takes a sequence of parameters and returns an SplObjectStorage object
     * that maps each paramter to a boolean indicating whether or not it was
     * present in $parameters.
     *
     * If $parameters is null then the default parameters (AUTO_DELETE) are returned.
     *
     * @param array<ExchangeParameter>|null $parameters The parameters sequence, or null to use the defaults.
     *
     * @return SplObjectStorage<ExchangeParameter, boolean> A map of parameter to boolean state.
     */
    public static function normalize(array $parameters = null)
    {
        $result = new SplObjectStorage();

        foreach (self::members() as $parameter) {
            $result[$parameter] = false;
        }

        if (null === $parameters) {
            $result[self::AUTO_DELETE()] = true;

            return $result;
        }

        foreach ($parameters as $parameter) {
            if (!$parameter instanceof self) {
                throw new InvalidArgumentException(
                    'Parameters must be instances of ' . __CLASS__ . '.'
                );
            }

            $result[$parameter] = true;
        }

        return $result;
    }
}
