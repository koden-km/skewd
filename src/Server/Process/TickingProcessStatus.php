<?php
namespace Skewd\Server\Process;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
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
