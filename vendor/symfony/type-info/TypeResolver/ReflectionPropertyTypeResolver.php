<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\TypeInfo\TypeResolver;

use OmniMailDeps\Symfony\Component\TypeInfo\Exception\UnsupportedException;
use OmniMailDeps\Symfony\Component\TypeInfo\Type;
use OmniMailDeps\Symfony\Component\TypeInfo\TypeContext\TypeContext;
use OmniMailDeps\Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
/**
 * Resolves type for a given property reflection.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class ReflectionPropertyTypeResolver implements TypeResolverInterface
{
    public function __construct(private readonly ReflectionTypeResolver $reflectionTypeResolver, private readonly TypeContextFactory $typeContextFactory)
    {
    }
    public function resolve(mixed $subject, ?TypeContext $typeContext = null): Type
    {
        if (!$subject instanceof \ReflectionProperty) {
            throw new UnsupportedException(\sprintf('Expected subject to be a "ReflectionProperty", "%s" given.', get_debug_type($subject)), $subject);
        }
        $typeContext ??= $this->typeContextFactory->createFromReflection($subject);
        try {
            return $this->reflectionTypeResolver->resolve($subject->getType(), $typeContext);
        } catch (UnsupportedException $e) {
            $path = \sprintf('%s::$%s', $subject->class, $subject->getName());
            throw new UnsupportedException(\sprintf('Cannot resolve type for "%s".', $path), $subject, previous: $e);
        }
    }
}
