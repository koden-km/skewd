<?php
namespace Skewd\Amqp;

use Eloquent\Enumeration\AbstractEnumeration;
use InvalidArgumentException;
use SplObjectStorage;

/**
 * Parameters used to configure a consumer.
 */
final class ConsumerParameter extends AbstractEnumeration
{
    /**
     * Do not consume messages published by the same connection as the consumer.
     */
    const NO_LOCAL = 'no_local';

    /**
     * Do not require the consumer to acknowledge messages.
     */
    const NO_ACK = 'no_ack';

    /**
     * Request exclusive access to the queue. This means that no other consumers
     * may exist on the queue.
     */
    const EXCLUSIVE = 'exclusive';

    /**
     * Normalize an array of parameters.
     *
     * Takes a sequence of parameters and returns an SplObjectStorage object
     * that maps each paramter to a boolean indicating whether or not it was
     * present in $parameters.
     *
     * If $parameters is null then the default parameters (none) are returned.
     *
     * @param array<ConsumerParameter>|null $parameters The parameters sequence, or null to use the defaults.
     *
     * @return SplObjectStorage<ConsumerParameter, boolean> A map of parameter to boolean state.
     */
    public static function normalize(array $parameters = null)
    {
        $result = new SplObjectStorage();

        foreach (self::members() as $parameter) {
            $result[$parameter] = false;
        }

        if (null === $parameters) {
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
