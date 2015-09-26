<?php
namespace Skewd\Common\Messaging;

/**
 * Generate random values suitable for use as node IDs.
 */
interface NodeIdGenerator
{
    /**
     * Generate random node IDs.
     *
     * @param integer $count The number of IDs to generate.
     *
     * @return array<string> The generated IDs.
     */
    public function generate($count);
}
