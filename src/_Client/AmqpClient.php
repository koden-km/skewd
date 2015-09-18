<?php
namespace Skewd\Client;

use Icecave\SemVer\Version;
use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Common\Session\Session;

final class AmqpClient implements Client
{
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Create a new session.
     *
     * @return Session
     */
    public function session()
    {
        // return new Session(...)
    }

    /**
     * Create a service client.
     *
     * @param Session $session The session to which the client is bound.
     * @param string  $name    The service name.
     * @param Version $version The required service version.
     *
     * @return ServiceClient
     */
    public function service(Session $session, $name, Version $version)
    {
    }

    private $channel;
}
