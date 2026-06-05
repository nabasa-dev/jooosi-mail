<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver;

use OmniMailDeps\Doctrine\DBAL\Driver;
interface Middleware
{
    public function wrap(Driver $driver): Driver;
}
