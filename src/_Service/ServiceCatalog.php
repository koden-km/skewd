<?php
namespace Skewd\Service;

use Guzzle\Common\Version;
use Icecave\SemVer\Version;

interface ServiceCatalog
{
    /**
     * Find any services that match the given version requirement.
     *
     * @param string $name The service name.
     * @param Version $version The requested version.
     *
     * @return array<Service>
     */
    public function match($name, Version $version);
}
