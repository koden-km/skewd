<?php
namespace Skewd\Client;

use Skewd\Service\Service;
use Skewd\Session\Session;

interface ServiceClient extends Service
{
    /**
     * @return Session
     */
    public function session();
}
