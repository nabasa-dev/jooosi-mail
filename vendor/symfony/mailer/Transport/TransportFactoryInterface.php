<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\Transport;

use OmniMailDeps\Symfony\Component\Mailer\Exception\IncompleteDsnException;
use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
/**
 * @author Konstantin Myakshin <molodchick@gmail.com>
 */
interface TransportFactoryInterface
{
    /**
     * @throws UnsupportedSchemeException
     * @throws IncompleteDsnException
     */
    public function create(Dsn $dsn): TransportInterface;
    public function supports(Dsn $dsn): bool;
}
