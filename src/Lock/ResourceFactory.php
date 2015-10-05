<?php
namespace Skewd\Lock;

/**
 * Creates lockable resource objects identified by a name.
 */
interface ResourceFactory
{
    /**
     * Create a resource.
     *
     * @param string $name The name of the resource.
     *
     * @return Lockable
     */
    public function createResource($name);
}
