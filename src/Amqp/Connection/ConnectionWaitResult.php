<?php
namespace Skewd\Amqp\Connection;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * The result of a call to Connection::wait().
 *
 * @see Connection::wait()
 */
final class ConnectionWaitResult extends AbstractEnumeration
{
    /**
     * Indicates that activity has occurred on the connection.
     */
    const NORMAL = 'normal';

    /**
     * The wait operation timed out before activity occurred.
     */
    const TIMEOUT = 'timeout';

    /**
     * The wait operation was interrupted by a signal before activity occurred.
     */
    const SIGNAL = 'signal';
}
