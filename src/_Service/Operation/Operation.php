<?php
namespace Skewd\Service\Operation;

/**
 * A service operation.
 */
interface Operation
{
    /**
     * Invoke the operation.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function invoke(Request $request, Response $response);
}
