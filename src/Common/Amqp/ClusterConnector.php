<?php
namespace Skewd\Common\Amqp;

use Icecave\Isolator\IsolatorTrait;
use InvalidArgumentException;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;

/**
 * A connector that creates AMQP connections by randomly selecting from a set of
 * other connectors.
 *
 * This connector is intended for establishing connections to a random server in
 * an AMQP cluster.
 */
final class ClusterConnector implements Connector
{
    /**
     * Create a cluster connector.
     *
     * @param Connector $connector The initial connectors to add.
     * @param Connector ...$connectors Additional connectors to add.
     *
     * @return ClusterConnector
     */
    public static function create(Connector $connector, Connector ...$connectors)
    {
        return new self(func_get_args());
    }

    /**
     * Connect to an AMQP server.
     *
     * The list of internal connections is tried in random order until one
     * successfully connects.  If none of the attempts are successful, the AMQP
     * exception from the last attempt is re-thrown.
     *
     * @return AbstractConnection     The server connection.
     * @throws AMQPExceptionInterface The connection could not be established.
     */
    public function connect()
    {
        $connectors = $this->connectors;
        $this->isolator()->shuffle($connectors);

        $exception = null;

        foreach ($connectors as $connector) {
            try {
                return $connector->connect();
            } catch (AMQPExceptionInterface $e) {
                $exception = $e;
            }
        }

        throw $exception;
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
     * @see ClusterConnector::create()
     *
     * @param array<Connector> $connectors
     */
    public function __construct(array $connectors)
    {
        if (!$connectors) {
            throw new InvalidArgumentException(
                'At least one connector must be provided.'
            );
        }

        $this->connectors = array_map(
            function (Connector $c) { return $c; },
            $connectors
        );
    }

    use IsolatorTrait;

    private $connectors;
}
