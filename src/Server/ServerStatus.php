<?php
namespace Skewd\Server;

use Eloquent\Enumeration\AbstractEnumeration;

class ServerStatus extends AbstractEnumeration
{
    const STARTING   = 'starting';
    const RUNNING    = 'running';
    const RESTARTING = 'restarting';
    const ERROR      = 'error';
    const STOPPING   = 'stopping';
    const STOPPED    = 'stopped';
}
