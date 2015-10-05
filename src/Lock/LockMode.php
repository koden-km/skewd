<?php
namespace Skewd\Lock;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * A lock mode defines the way that lock requests on a single resource interact.
 */
final class LockMode extends AbstractEnumeration
{
    /**
     * A shared lock can be acquired on any resource that does not have an
     * existing exclusive lock.
     */
    const SHARED = 'shared';

    /**
     * An exclusive lock can only be acquired on resources that have no existing
     * locks of any kind.
     */
    const EXCLUSIVE = 'exclusive';
}
