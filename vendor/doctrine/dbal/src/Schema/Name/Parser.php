<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\Name;

use OmniMailDeps\Doctrine\DBAL\Schema\Name;
use OmniMailDeps\Doctrine\DBAL\Schema\Name\Parser\Exception;
/**
 * Parses a database object name.
 *
 * @internal
 *
 * @template N of Name
 */
interface Parser
{
    /**
     * @return N
     *
     * @throws Exception
     */
    public function parse(string $input): Name;
}
