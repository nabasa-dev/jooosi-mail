<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\Mysqli\Initializer;

use OmniMailDeps\Doctrine\DBAL\Driver\Mysqli\Exception\InvalidOption;
use OmniMailDeps\Doctrine\DBAL\Driver\Mysqli\Initializer;
use mysqli;
use function mysqli_options;
final class Options implements Initializer
{
    /** @param array<int,mixed> $options */
    public function __construct(private readonly array $options)
    {
    }
    public function initialize(mysqli $connection): void
    {
        foreach ($this->options as $option => $value) {
            if (!mysqli_options($connection, $option, $value)) {
                throw InvalidOption::fromOption($option, $value);
            }
        }
    }
}
