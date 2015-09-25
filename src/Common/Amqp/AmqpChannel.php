<?php
namespace Skewd\Common\Amqp;

use PhpAmqpLib\Channel\AMQPChannel as AMQPChannelImpl;
use Skewd\Common\Messaging\Channel;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * An AMQP channel based on a "videlalvaro/php-amqplib" AMQP channel.
 */
final class AmqpChannel implements Channel
{
    /**
     * @param AMQPChannelImpl $channel The underlying channel.
     */
    public function __construct(AMQPChannelImpl $channel)
    {
        $this->channel = $channel;
    }

    private $channel;
}
