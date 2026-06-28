<?php

declare (strict_types=1);
namespace JooosiMailDeps\Doctrine\DBAL\Schema\Name;

use JooosiMailDeps\Doctrine\DBAL\Schema\Name;
use JooosiMailDeps\Doctrine\DBAL\Schema\Name\Parser\Exception;
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
