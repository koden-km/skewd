<?php
namespace Skewd\Amqp;

use Eloquent\Enumeration\AbstractEnumeration;
use InvalidArgumentException;
use SplObjectStorage;

/**
 * Parameters used to configure an exchange.
 */
final class QueueParameter extends AbstractEnumeration
{
    /**
     * Confirm that the queue exists and matches the configured parameters,
     * but do not create it.
     */
    const PASSIVE = 'passive';

    /**
     * Persist the queue (but not necessarily the messages on it) across server
     * restarts.
     */
    const DURABLE = 'durable';

    /**
     * Restrict access to the queue to the connection used to declare it.
     */
    const EXCLUSIVE = 'exclusive';

    /**
     * Delete the queue once all consumers have been cancelled.
     */
    const AUTO_DELETE = 'auto_delete';

    /**
     * Normalize an array of parameters.
     *
     * Takes a sequence of parameters and returns an SplObjectStorage object
     * that maps each paramter to a boolean indicating whether or not it was
     * present in $parameters.
     *
     * If $parameters is null then the default parameters are returned.
     *
     * @param array<QueueParameter>|null $parameters The parameters sequence, or null to use the defaults.
     *
     * @return SplObjectStorage<QueueParameter, boolean> A map of parameter to boolean state.
     */
    public static function normalize(array $parameters = null)
    {
        $result = new SplObjectStorage();

        foreach (self::members() as $parameter) {
            $result[$parameter] = false;
        }

        if (null === $parameters) {
            $result[self::EXCLUSIVE()] = true;
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
