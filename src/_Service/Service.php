<?php
namespace Skewd\Service;

use Icecave\SemVer\Version;

/**
 * A service is a versioned collection of operations and notification topics.
 */
interface Service
{
    /**
     * Get the service name.
     *
     * @return string
     */
    public function name();

    /**
     * @return Version
     */
    public function version();

    /**
     * @return Operation
     */
    public function operation($name);

    /**
     * @return Topic
     */
    public function topic($name);
}
