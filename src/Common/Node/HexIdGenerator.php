<?php
namespace Skewd\Common\Node;

use Icecave\Isolator\IsolatorTrait;

/**
 * Generates random hexadecimal strings suitable for use as node IDs.
 */
final class HexIdGenerator implements NodeIdGenerator
{
    /**
     * Create a hex node ID generator.
     *
     * @param integer $length The length of the generated IDs, in bytes.
     *
     * @return HexNodeIdGenerator
     */
    public static function create($length = self::DEFAULT_LENGTH_IN_BYTES)
    {
        return new self($length);
    }

    /**
     * Generate random node IDs.
     *
     * @param integer $count The number of unique IDs to generate.
     *
     * @return array<string> The generated IDs.
     */
    public function generate($count)
    {
        $result = [];

        while (count($result) < $count) {
            do {
                $id = $this->generateRandomId();
            } while (isset($result[$id]));

            $result[$id] = true;
        }

        return array_keys($result);
    }

    /**
     * Please note that this code is not part of the public API. It may be
     * changed or removed at any time without notice.
     *
     * @access private
     *
     * This constructor is public so that it may be used by auto-wiring
     * dependency injection containers. If you are explicitly constructing an
     * instance please use one of the static factory methods listed below.
     *
     * @see HexNodeIdGenerator::create()
     *
     * @param integer $length The length of the generated IDs, in bytes.
     */
    public function __construct($length)
    {
        $this->length = $length;
    }

    /**
     * Generate a new random ID.
     *
     * @return string
     */
    private function generateRandomId()
    {
        $iso = $this->isolator();
        $id = '';

        for ($i = 0; $i < $this->length; ++$i) {
            $id .= sprintf(
                '%02x',
                $iso->mt_rand(0, 0xff)
            );
        }

        return $id;
    }

    use IsolatorTrait;

    const DEFAULT_LENGTH_IN_BYTES = 2;

    /**
     * @var integer The length of the generated IDs, in bytes.
     */
    private $length;
}
