<?php
namespace Skewd\Common\Messaging;

/**
 * A node on the network.
 */
interface Node
{
    /**
     * Get a publisher for the given domain.
     *
     * @param string $domain The domain within which the publisher communicates.
     *
     * @return Publisher The publisher.
     */
    public function publisher($domain);

    /**
     * Get a subscriber for the given domain.
     *
     * @param string $domain The domain within which the subscriber communicates.
     *
     * @return Subscriber The subscriber.
     */
    public function subscriber($domain);
}
