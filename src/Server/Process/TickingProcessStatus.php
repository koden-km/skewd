<?php
namespace Skewd\Server\Process;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @access private
 */
final class TickingProcessStatus extends AbstractEnumeration
{
    const STARTING   = 'starting';
    const RUNNING    = 'running';
    const RESTARTING = 'restarting';
    const ERROR      = 'error';
    const STOPPING   = 'stopping';
    const STOPPED    = 'stopped';
}
