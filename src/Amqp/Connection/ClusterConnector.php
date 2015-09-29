<?php
namespace Skewd\Amqp\Connection;

use Icecave\Isolator\IsolatorTrait;

/**
 * A connector for connecting to one AMQP server within a cluster.
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
     * The list of internal connectors is tried in random order until one
     * successfully connects.  If none of the attempts are successful, the
     * exception from the last attempt is re-thrown.
     *
     * @return Connection          The AMQP connection.
     * @throws ConnectionException If the connection could not be established.
     */
    public function connect()
    {
        $connectors = $this->connectors;
        $this->isolator()->shuffle($connectors);

        $exception = null;

        foreach ($connectors as $connector) {
            try {
                return $connector->connect();
            } catch (ConnectionException $e) {
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
        call_user_func_array(
            function (Connector $x, Connector ...$x) {},
            $connectors
        );

        $this->connectors = $connectors;
    }

    use IsolatorTrait;

    private $connectors;
}
