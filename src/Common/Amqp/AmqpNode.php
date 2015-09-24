<?php
namespace Skewd\Common\Amqp;

use Skewd\Common\Messaging\Node;

final class AmqpNode implements Node
{
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
        $this->publishers = [];
        $this->subscribers = [];
    }

    /**
     * Get a publisher for the given domain.
     *
     * @param string $domain The domain within which the publisher communicates.
     *
     * @return Publisher The publisher.
     */
    public function publisher($domain)
    {
        if (isset($this->publishers[$domain])) {
            return $this->publishers[$domain];
        }

        $publisher = new AmqpPublisher($channel, $domain);

        $this->publishers[$domain] = $publisher;

        return $publisher;
    }

    /**
     * Get a subscriber for the given domain.
     *
     * @param string $domain The domain within which the subscriber communicates.
     *
     * @return Subscriber The subscriber.
     */
    public function subscriber($domain)
    {
        if (isset($this->subscribers[$domain])) {
            return $this->subscribers[$domain];
        }

        $subscriber = new AmqpSubscriber($channel, $domain);

        $this->subscribers[$domain] = $subscriber;

        return $subscriber;
    }

    private $channel;
}
