<?php
namespace Skewd\Amqp;

use Eloquent\Enumeration\AbstractEnumeration;
use InvalidArgumentException;
use SplObjectStorage;

/**
 * Options used to configure the behaviour of message publishing.
 */
final class PublishOption extends AbstractEnumeration
{
    /**
     * Fail if the server is unable to route the message to any queues.
     *
     * If this option is OFF, messages that are not routed to any queues are
     * silently dropped.
     */
    const MANDATORY = 'mandatory';

    /**
     * Only place a message on a queue if there are currently ready consumers
     * on that queue.
     *
     * @deprecated This feature is no longer supported by Rabbit MQ.
     * @link http://www.rabbitmq.com/blog/2012/11/19/breaking-things-with-rabbitmq-3-0/
     */
    const IMMEDIATE = 'immediate';

    /**
     * Normalize an array of options.
     *
     * Takes a sequence of options and returns an SplObjectStorage object
     * that maps each paramter to a boolean indicating whether or not it was
     * present in $options.
     *
     * If $options is null then the default options (none) are returned.
     *
     * @param array<PublishOption>|null $options The options sequence, or null to use the defaults.
     *
     * @return SplObjectStorage<PublishOption, boolean> A map of option to boolean state.
     */
    public static function normalize(array $options = null)
    {
        $result = new SplObjectStorage();

        foreach (self::members() as $option) {
            $result[$option] = false;
        }

        if (null === $options) {
            return $result;
        }

        foreach ($options as $option) {
            if (!$option instanceof self) {
                throw new InvalidArgumentException(
                    'Options must be instances of ' . __CLASS__ . '.'
                );
            }

            $result[$option] = true;
        }

        return $result;
    }
}
