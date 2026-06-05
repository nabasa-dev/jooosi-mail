<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Logging;

use OmniMailDeps\Doctrine\DBAL\Driver as DriverInterface;
use OmniMailDeps\Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use OmniMailDeps\Psr\Log\LoggerInterface;
final class Middleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }
    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new Driver($driver, $this->logger);
    }
}
