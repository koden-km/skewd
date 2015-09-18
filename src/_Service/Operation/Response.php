<?php
namespace Skewd\Service\Operation;

interface Response
{
    public function keepAlive();

    public function done($result = null);
}
