<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Messenger\Middleware;

use OmniMailDeps\Symfony\Component\Messenger\Envelope;
use OmniMailDeps\Symfony\Component\Messenger\Exception\ValidationFailedException;
use OmniMailDeps\Symfony\Component\Messenger\Stamp\ValidationStamp;
use OmniMailDeps\Symfony\Component\Validator\Validator\ValidatorInterface;
/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ValidationMiddleware implements MiddlewareInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $groups = $envelope->last(ValidationStamp::class)?->getGroups();
        $violations = $this->validator->validate($message, null, $groups);
        if (\count($violations)) {
            throw new ValidationFailedException($message, $violations, $envelope);
        }
        return $stack->next()->handle($envelope, $stack);
    }
}
