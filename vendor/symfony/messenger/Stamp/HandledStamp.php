<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Messenger\Stamp;

use OmniMailDeps\Symfony\Component\Messenger\Handler\HandlerDescriptor;
use OmniMailDeps\Symfony\Component\Messenger\HandleTrait;
use OmniMailDeps\Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
/**
 * Stamp identifying a message handled by the `HandleMessageMiddleware` middleware
 * and storing the handler returned value.
 *
 * This is used by synchronous command buses expecting a return value and the retry logic
 * to only execute handlers that didn't succeed.
 *
 * @see HandleMessageMiddleware
 * @see HandleTrait
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
final class HandledStamp implements StampInterface
{
    public function __construct(private mixed $result, private string $handlerName)
    {
    }
    public static function fromDescriptor(HandlerDescriptor $handler, mixed $result): self
    {
        return new self($result, $handler->getName());
    }
    public function getResult(): mixed
    {
        return $this->result;
    }
    public function getHandlerName(): string
    {
        return $this->handlerName;
    }
}
