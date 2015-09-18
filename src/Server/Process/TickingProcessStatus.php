<?php
namespace Skewd\Server\Process;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * *** THIS TYPE IS NOT PART OF THE PUBLIC API ***
 *
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
