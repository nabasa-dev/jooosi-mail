<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Uid\Factory;

use JooosiMailDeps\Symfony\Component\Uid\UuidV4;
class RandomBasedUuidFactory
{
    /**
     * @param class-string $class
     */
    public function __construct(private string $class)
    {
    }
    public function create(): UuidV4
    {
        $class = $this->class;
        return new $class();
    }
}
