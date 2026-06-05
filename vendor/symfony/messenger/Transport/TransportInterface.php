<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Messenger\Transport;

use OmniMailDeps\Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use OmniMailDeps\Symfony\Component\Messenger\Transport\Sender\SenderInterface;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
interface TransportInterface extends ReceiverInterface, SenderInterface
{
}
