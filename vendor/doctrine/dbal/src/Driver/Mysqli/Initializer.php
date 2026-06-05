<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\Mysqli;

use OmniMailDeps\Doctrine\DBAL\Driver\Exception;
use mysqli;
interface Initializer
{
    /** @throws Exception */
    public function initialize(mysqli $connection): void;
}
