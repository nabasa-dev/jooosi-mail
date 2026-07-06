<?php

declare(strict_types=1);

namespace JooosiMail\Rector\Rule;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Downgrades PHP 8.4 asymmetric property visibility to normal PHP 8.3 visibility.
 *
 * @since 1.0.7
 */
final class DowngradeAsymmetricVisibilityRector extends AbstractRector
{
    /**
     * Describes the refactoring performed by this rule.
     *
     * @since 1.0.7
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade PHP 8.4 asymmetric property visibility to normal PHP 8.3 visibility', [new CodeSample(<<<'CODE_SAMPLE'
final class Location
{
    public function __construct(
        public readonly string $path,
        private(set) array $ignore = [],
    ) {}
}
CODE_SAMPLE
        , <<<'CODE_SAMPLE'
final class Location
{
    public function __construct(
        public readonly string $path,
        public array $ignore = [],
    ) {}
}
CODE_SAMPLE
        )]);
    }

    /**
     * Returns node types handled by this rule.
     *
     * @return array<class-string<Node>>
     *
     * @since 1.0.7
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Trait_::class];
    }

    /**
     * Removes asymmetric visibility flags from properties and promoted parameters.
     *
     * @param Class_|Trait_ $node
     *
     * @since 1.0.7
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->stmts as $statement) {
            if ($statement instanceof Property) {
                $hasChanged = $this->stripAsymmetricVisibility($statement->flags) || $hasChanged;

                continue;
            }

            if (! $statement instanceof ClassMethod) {
                continue;
            }

            foreach ($statement->params as $param) {
                $hasChanged = $this->stripAsymmetricVisibility($param->flags) || $hasChanged;
            }
        }

        return $hasChanged ? $node : null;
    }

    /**
     * Removes asymmetric visibility flags while preserving public reads.
     *
     * @since 1.0.7
     */
    private function stripAsymmetricVisibility(int &$flags): bool
    {
        $setVisibilityMask = Modifiers::PUBLIC_SET | Modifiers::PROTECTED_SET | Modifiers::PRIVATE_SET;

        if (($flags & $setVisibilityMask) === 0) {
            return false;
        }

        $flags &= ~$setVisibilityMask;

        if (($flags & Modifiers::VISIBILITY_MASK) === 0) {
            $flags |= Modifiers::PUBLIC;
        }

        return true;
    }
}
