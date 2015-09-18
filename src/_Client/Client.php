<?php
namespace Skewd\Client;

use Icecave\SemVer\Version;
use Skewd\Session\Session;

interface Client
{
    /**
     * Create a new session.
     *
     * @return Session
     */
    public function session();

    /**
     * Create a service client.
     *
     * @param Session $session The session to which the client is bound.
     * @param string  $name    The service name.
     * @param Version $version The required service version.
     *
     * @return ServiceClient
     */
    public function service(Session $session, $name, Version $version);
}
