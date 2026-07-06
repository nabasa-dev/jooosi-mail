<?php

declare(strict_types=1);

namespace JooosiMail\Rector\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_ as ArrayExpr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Downgrades PHP 8.5 clone-with expressions to constructor calls where safe.
 *
 * @since 1.0.7
 */
final class DowngradeCloneWithRector extends AbstractRector
{
    /**
     * Describes the refactoring performed by this rule.
     *
     * @since 1.0.7
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade PHP 8.5 clone-with expressions to constructor calls where safe', [new CodeSample(<<<'CODE_SAMPLE'
final class Cache
{
    public function __construct(private readonly Strategy $strategy) {}

    public function withStrategy(Strategy $strategy): self
    {
        return clone($this, ['strategy' => $strategy]);
    }
}
CODE_SAMPLE
        , <<<'CODE_SAMPLE'
final class Cache
{
    public function __construct(private readonly Strategy $strategy) {}

    public function withStrategy(Strategy $strategy): self
    {
        return new self(strategy: $strategy);
    }
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
        return [Class_::class];
    }

    /**
     * Rewrites clone($this, ['property' => $value]) in class methods.
     *
     * @param Class_ $node
     *
     * @since 1.0.7
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->getMethods() as $classMethod) {
            if ($classMethod->stmts === null) {
                continue;
            }

            $this->traverseNodesWithCallable($classMethod->stmts, function (Node $candidate) use ($node, &$hasChanged): ?Node {
                if (! $candidate instanceof FuncCall) {
                    return null;
                }

                $newSelf = $this->createNewSelfFromCloneWith($node, $candidate);

                if (! $newSelf instanceof New_) {
                    return null;
                }

                $hasChanged = true;

                return $newSelf;
            });
        }

        return $hasChanged ? $node : null;
    }

    /**
     * Creates a constructor call equivalent for clone($this, ['property' => $value]).
     *
     * @since 1.0.7
     */
    private function createNewSelfFromCloneWith(Class_ $class, FuncCall $funcCall): ?New_
    {
        if (! $this->isName($funcCall, 'clone')) {
            return null;
        }

        if (count($funcCall->args) !== 2) {
            return null;
        }

        $clonedExpression = $funcCall->args[0]->value;
        if (! $clonedExpression instanceof Variable || $clonedExpression->name !== 'this') {
            return null;
        }

        $overrideExpression = $funcCall->args[1]->value;
        if (! $overrideExpression instanceof ArrayExpr) {
            return null;
        }

        $constructor = $class->getMethod('__construct');
        if (! $constructor instanceof ClassMethod) {
            return null;
        }

        $overrides = $this->resolveCloneWithOverrides($overrideExpression);
        if ($overrides === null) {
            return null;
        }

        $usedOverrides = [];
        $args = [];

        foreach ($constructor->params as $param) {
            $paramName = $this->getName($param);

            if ($paramName === null) {
                return null;
            }

            if (array_key_exists($paramName, $overrides)) {
                $args[] = new Arg($overrides[$paramName], name: new Identifier($paramName));
                $usedOverrides[$paramName] = true;

                continue;
            }

            if ($param->isPromoted()) {
                $args[] = new Arg(new PropertyFetch(new Variable('this'), $paramName), name: new Identifier($paramName));

                continue;
            }

            if ($param->default !== null) {
                continue;
            }

            return null;
        }

        foreach (array_keys($overrides) as $propertyName) {
            if (! isset($usedOverrides[$propertyName])) {
                return null;
            }
        }

        return new New_(new Name('self'), $args);
    }

    /**
     * Resolves clone-with array overrides.
     *
     * @return array<string, Expr>|null
     *
     * @since 1.0.7
     */
    private function resolveCloneWithOverrides(ArrayExpr $array): ?array
    {
        $overrides = [];

        foreach ($array->items as $arrayItem) {
            if ($arrayItem === null || ! $arrayItem->key instanceof String_) {
                return null;
            }

            $overrides[$arrayItem->key->value] = $arrayItem->value;
        }

        return $overrides;
    }
}
