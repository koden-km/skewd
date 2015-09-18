<?php
namespace Skewd\Service\Operation;

use Skewd\Session\Session;

interface Request
{
    /**
     * @return Session
     */
    public function session();

    /**
     * @return string
     */
    public function operation();

    /**
     * @return array<string, mixed>
     */
    public function arguments();
}
