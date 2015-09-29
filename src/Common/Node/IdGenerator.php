<?php
namespace Skewd\Common\Node;

/**
 * Generate random values suitable for use as node IDs.
 */
interface IdGenerator
{
    /**
     * Generate random node IDs.
     *
     * @param integer $count The number of unique IDs to generate.
     *
     * @return array<string> The generated IDs.
     */
    public function generate($count);
}
