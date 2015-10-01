<?php
namespace Skewd\Amqp;

use Exception;
use RuntimeException;
use SplObjectStorage;

/**
 * An error occured while attempting to declare an exchange or queue.
 */
final class DeclareException extends RuntimeException
{
    /**
     * Create an exception that indicates a failure to declare an exchange
     * because it already exists with a different type or parameters.
     *
     * @param string           $name       The name of the exchange.
     * @param ExchangeType     $type       The type of the exchange used in the failed attempt.
     * @param SplObjectStorage $parameters The parameters used in the failed attempt.
     * @param Exception|null   $previous   The exception that caused this exception, if any.
     *
     * @return DeclareException
     */
    public static function exchangeTypeOrParameterMismatch(
        $name,
        ExchangeType $type,
        SplObjectStorage $parameters,
        Exception $previous = null)
    {
        $parameterKeys = [];

        foreach ($parameters as $parameter) {
            if ($parameters[$parameter]) {
                $parameterKeys[] = $parameter->key();
            }
        }

        return new self(
            sprintf(
                'Failed to declare exchange "%s", type "%s" or parameters [%s] do not match the server.',
                $name,
                $type->key(),
                implode(', ', $parameterKeys)
            ),
            0,
            $previous
        );
    }

    /**
     * Create an exception that indicates a failure to declare a queue because
     * it already exists with different parameters.
     *
     * @param string           $name       The name of the queue.
     * @param SplObjectStorage $parameters The parameters used in the failed attempt.
     * @param Exception|null   $previous   The exception that caused this exception, if any.
     *
     * @return DeclareException
     */
    public static function queueParameterMismatch(
        $name,
        SplObjectStorage $parameters,
        Exception $previous = null)
    {
        $parameterKeys = [];

        foreach ($parameters as $parameter) {
            if ($parameters[$parameter]) {
                $parameterKeys[] = $parameter->key();
            }
        }

        return new self(
            sprintf(
                'Failed to declare queue "%s", parameters [%s] do not match the server.',
                $name,
                implode(', ', $parameterKeys)
            ),
            0,
            $previous
        );
    }
}
