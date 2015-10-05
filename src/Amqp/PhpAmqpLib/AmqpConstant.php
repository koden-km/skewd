<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * AMQP protocol constants.
 */
final class AmqpConstant extends AbstractEnumeration
{
    const ACCESS_REFUSED = 403;
    const NOT_FOUND = 404;
    const RESOURCE_LOCKED = 405;
    const PRECONDITION_FAILED = 406;
}
