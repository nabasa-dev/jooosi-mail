<?php

declare(strict_types=1);

namespace JooosiMail\Rector\Rule;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyHook;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Downgrades PHP 8.4 property hooks into explicit methods and magic property access bridges.
 *
 * @since 1.0.7
 */
final class DowngradePropertyHookRector extends AbstractRector
{
    /**
     * Describes the refactoring performed by this rule.
     *
     * @since 1.0.7
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade PHP 8.4 property hooks to PHP 8.3-compatible methods', [new CodeSample(<<<'CODE_SAMPLE'
final class DiscoveryCache
{
    public bool $enabled {
        get => $this->valid && $this->strategy->isEnabled();
    }
}
CODE_SAMPLE
        , <<<'CODE_SAMPLE'
final class DiscoveryCache
{
    public function getEnabled(): bool
    {
        return $this->getValid() && $this->strategy->isEnabled();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'enabled') {
            return $this->getEnabled();
        }

        throw new \RuntimeException(sprintf('Undefined property: %s::$%s', self::class, $name));
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
        return [Class_::class, Interface_::class];
    }

    /**
     * Downgrades hooked properties within a class.
     *
     * @param Class_ $node
     *
     * @since 1.0.7
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Interface_) {
            return $this->refactorInterface($node);
        }

        $hasChanged = false;
        $newStatements = [];
        $virtualPropertyGetters = [];
        $magicGetters = [];
        $magicSetters = [];

        foreach ($node->stmts as $statement) {
            if (! $statement instanceof Property || $statement->hooks === []) {
                $newStatements[] = $statement;

                continue;
            }

            if (count($statement->props) !== 1) {
                $newStatements[] = $statement;

                continue;
            }

            $propertyName = $statement->props[0]->name->toString();
            $classMethodMap = $this->createClassMethodMap($statement, $propertyName, $node);

            if ($classMethodMap['methods'] === []) {
                $newStatements[] = $statement;

                continue;
            }

            if ($classMethodMap['getter'] !== null) {
                $magicGetters[$propertyName] = $classMethodMap['getter'];
            }

            if ($classMethodMap['setter'] !== null) {
                $magicSetters[$propertyName] = $classMethodMap['setter'];
            }

            if ($this->isVirtualProperty($statement, $propertyName)) {
                $virtualPropertyGetters[$propertyName] = $this->createGetterMethodName($propertyName);
            } else {
                $statement->hooks = [];
                $this->makePropertyPrivate($statement);
                $newStatements[] = $statement;
            }

            foreach ($classMethodMap['methods'] as $classMethod) {
                $newStatements[] = $classMethod;
            }

            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        $node->stmts = $newStatements;

        if ($virtualPropertyGetters !== []) {
            $this->replaceVirtualPropertyFetches($node->stmts, $virtualPropertyGetters);
        }

        $this->appendMagicAccessors($node, $magicGetters, $magicSetters);

        return $node;
    }

    /**
     * Removes hooked interface properties because PHP 8.3 has no equivalent interface property contract.
     *
     * @since 1.0.7
     */
    private function refactorInterface(Interface_ $interface): ?Interface_
    {
        $hasChanged = false;
        $newStatements = [];

        foreach ($interface->stmts as $statement) {
            if ($statement instanceof Property && $statement->hooks !== []) {
                $hasChanged = true;

                continue;
            }

            $newStatements[] = $statement;
        }

        if (! $hasChanged) {
            return null;
        }

        $interface->stmts = $newStatements;

        return $interface;
    }

    /**
     * Creates methods for hooks on a property.
     *
     * @return array{methods: list<ClassMethod>, getter: string|null, setter: string|null}
     *
     * @since 1.0.7
     */
    private function createClassMethodMap(Property $property, string $propertyName, Class_ $class): array
    {
        $classMethodMap = [
            'methods' => [],
            'getter' => null,
            'setter' => null,
        ];

        foreach ($property->hooks as $hook) {
            $hookName = $this->getHookName($hook);

            if ($hookName === 'get') {
                $methodName = $this->createGetterMethodName($propertyName);
            } elseif ($hookName === 'set') {
                $methodName = $this->createSetterMethodName($propertyName);
            } else {
                continue;
            }

            if ($class->getMethod($methodName) instanceof ClassMethod) {
                return ['methods' => [], 'getter' => null, 'setter' => null];
            }

            if ($hookName === 'get') {
                $classMethodMap['getter'] = $methodName;
                $classMethodMap['methods'][] = $this->createGetterClassMethod($hook, $methodName, $property);
            } else {
                $classMethodMap['setter'] = $methodName;
                $classMethodMap['methods'][] = $this->createSetterClassMethod($hook, $methodName, $property, $propertyName);
            }
        }

        return $classMethodMap;
    }

    /**
     * Creates a getter method from a get hook.
     *
     * @since 1.0.7
     */
    private function createGetterClassMethod(PropertyHook $hook, string $methodName, Property $property): ClassMethod
    {
        return new ClassMethod($methodName, [
            'attrGroups' => $hook->attrGroups,
            'byRef' => $hook->byRef,
            'flags' => $this->createMethodFlags($hook),
            'returnType' => $property->type,
            'stmts' => $this->createGetterStatements($hook),
        ]);
    }

    /**
     * Creates a setter method from a set hook.
     *
     * @since 1.0.7
     */
    private function createSetterClassMethod(PropertyHook $hook, string $methodName, Property $property, string $propertyName): ClassMethod
    {
        return new ClassMethod($methodName, [
            'attrGroups' => $hook->attrGroups,
            'flags' => $this->createMethodFlags($hook),
            'params' => $this->createSetterParams($hook, $property),
            'returnType' => new Identifier('void'),
            'stmts' => $this->createSetterStatements($hook, $propertyName),
        ]);
    }

    /**
     * Creates statements for a getter method.
     *
     * @return list<Stmt>|null
     *
     * @since 1.0.7
     */
    private function createGetterStatements(PropertyHook $hook): ?array
    {
        if ($hook->body instanceof Expr) {
            return [new Return_($hook->body)];
        }

        return $hook->body;
    }

    /**
     * Creates statements for a setter method.
     *
     * @return list<Stmt>|null
     *
     * @since 1.0.7
     */
    private function createSetterStatements(PropertyHook $hook, string $propertyName): ?array
    {
        if ($hook->body instanceof Expr) {
            return [
                new Expression(new Assign(
                    new PropertyFetch(new Variable('this'), $propertyName),
                    $hook->body,
                )),
            ];
        }

        return $hook->body;
    }

    /**
     * Creates setter parameters, including the implicit PHP property-hook value.
     *
     * @return list<Param>
     *
     * @since 1.0.7
     */
    private function createSetterParams(PropertyHook $hook, Property $property): array
    {
        if ($hook->params !== []) {
            return $hook->params;
        }

        return [new Param(new Variable('value'), type: $property->type)];
    }

    /**
     * Creates public method flags while preserving final hooks.
     *
     * @since 1.0.7
     */
    private function createMethodFlags(PropertyHook $hook): int
    {
        $flags = Modifiers::PUBLIC;

        if ($hook->isFinal()) {
            $flags |= Modifiers::FINAL;
        }

        if ($hook->body === null) {
            $flags |= Modifiers::ABSTRACT;
        }

        return $flags;
    }

    /**
     * Detects whether a hooked property has no backing storage.
     *
     * @since 1.0.7
     */
    private function isVirtualProperty(Property $property, string $propertyName): bool
    {
        if ($property->props[0]->default instanceof Expr) {
            return false;
        }

        foreach ($property->hooks as $hook) {
            if ($this->getHookName($hook) === 'set') {
                return false;
            }

            if ($this->containsSelfPropertyFetch($hook->body, $propertyName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether nodes reference $this->propertyName.
     *
     * @param Expr|list<Stmt>|null $nodes
     *
     * @since 1.0.7
     */
    private function containsSelfPropertyFetch($nodes, string $propertyName): bool
    {
        $containsSelfPropertyFetch = false;

        $this->traverseNodesWithCallable($nodes, function (Node $node) use (&$containsSelfPropertyFetch, $propertyName): ?Node {
            if ($node instanceof PropertyFetch && $this->isSelfPropertyFetch($node, $propertyName)) {
                $containsSelfPropertyFetch = true;
            }

            return null;
        });

        return $containsSelfPropertyFetch;
    }

    /**
     * Rewrites internal reads of removed virtual properties to generated getters.
     *
     * @param list<Stmt> $statements
     * @param array<string, string> $virtualPropertyGetters
     *
     * @since 1.0.7
     */
    private function replaceVirtualPropertyFetches(array $statements, array $virtualPropertyGetters): void
    {
        $this->traverseNodesWithCallable($statements, function (Node $node) use ($virtualPropertyGetters): ?Node {
            if (! $node instanceof PropertyFetch || ! $this->isSelfPropertyFetch($node)) {
                return null;
            }

            if (! $node->name instanceof Identifier) {
                return null;
            }

            $propertyName = $node->name->toString();

            if (! isset($virtualPropertyGetters[$propertyName])) {
                return null;
            }

            return new MethodCall(new Variable('this'), $virtualPropertyGetters[$propertyName]);
        });
    }

    /**
     * Adds magic property accessors for downgraded public hooked properties.
     *
     * @param array<string, string> $magicGetters
     * @param array<string, string> $magicSetters
     *
     * @since 1.0.7
     */
    private function appendMagicAccessors(Class_ $class, array $magicGetters, array $magicSetters): void
    {
        if ($magicGetters !== [] && ! $class->getMethod('__get') instanceof ClassMethod) {
            $class->stmts[] = $this->createMagicGetMethod($magicGetters);
        }

        if (($magicGetters !== [] || $magicSetters !== []) && ! $class->getMethod('__isset') instanceof ClassMethod) {
            $class->stmts[] = $this->createMagicIssetMethod($magicGetters);
        }

        if ($magicSetters !== [] && ! $class->getMethod('__set') instanceof ClassMethod) {
            $class->stmts[] = $this->createMagicSetMethod($magicSetters);
        }
    }

    /**
     * Creates a __get bridge method.
     *
     * @param array<string, string> $magicGetters
     *
     * @since 1.0.7
     */
    private function createMagicGetMethod(array $magicGetters): ClassMethod
    {
        $statements = [];

        foreach ($magicGetters as $propertyName => $methodName) {
            $statements[] = new If_(
                new Identical(new Variable('name'), new String_($propertyName)),
                ['stmts' => [new Return_(new MethodCall(new Variable('this'), $methodName))]],
            );
        }

        $statements[] = $this->createUndefinedPropertyThrow();

        return new ClassMethod('__get', [
            'flags' => Modifiers::PUBLIC,
            'params' => [new Param(new Variable('name'), type: new Identifier('string'))],
            'returnType' => new Identifier('mixed'),
            'stmts' => $statements,
        ]);
    }

    /**
     * Creates a __isset bridge method.
     *
     * @param array<string, string> $magicGetters
     *
     * @since 1.0.7
     */
    private function createMagicIssetMethod(array $magicGetters): ClassMethod
    {
        $statements = [];

        foreach ($magicGetters as $propertyName => $methodName) {
            $statements[] = new If_(
                new Identical(new Variable('name'), new String_($propertyName)),
                [
                    'stmts' => [
                        new Return_(new NotIdentical(
                            new MethodCall(new Variable('this'), $methodName),
                            new ConstFetch(new Name('null')),
                        )),
                    ],
                ],
            );
        }

        $statements[] = new Return_(new ConstFetch(new Name('false')));

        return new ClassMethod('__isset', [
            'flags' => Modifiers::PUBLIC,
            'params' => [new Param(new Variable('name'), type: new Identifier('string'))],
            'returnType' => new Identifier('bool'),
            'stmts' => $statements,
        ]);
    }

    /**
     * Creates a __set bridge method.
     *
     * @param array<string, string> $magicSetters
     *
     * @since 1.0.7
     */
    private function createMagicSetMethod(array $magicSetters): ClassMethod
    {
        $statements = [];

        foreach ($magicSetters as $propertyName => $methodName) {
            $statements[] = new If_(
                new Identical(new Variable('name'), new String_($propertyName)),
                [
                    'stmts' => [
                        new Expression(new MethodCall(new Variable('this'), $methodName, [new Arg(new Variable('value'))])),
                        new Return_(),
                    ],
                ],
            );
        }

        $statements[] = $this->createUndefinedPropertyThrow();

        return new ClassMethod('__set', [
            'flags' => Modifiers::PUBLIC,
            'params' => [
                new Param(new Variable('name'), type: new Identifier('string')),
                new Param(new Variable('value'), type: new Identifier('mixed')),
            ],
            'returnType' => new Identifier('void'),
            'stmts' => $statements,
        ]);
    }

    /**
     * Creates an undefined property exception throw.
     *
     * @since 1.0.7
     */
    private function createUndefinedPropertyThrow(): Expression
    {
        return new Expression(new Throw_(new New_(new FullyQualified('RuntimeException'), [
            new Arg(new FuncCall(new Name('sprintf'), [
                new Arg(new String_('Undefined property: %s::$%s')),
                new Arg(new ClassConstFetch(new Name('self'), 'class')),
                new Arg(new Variable('name')),
            ])),
        ])));
    }

    /**
     * Makes a backed hooked property private after explicit methods are created.
     *
     * @since 1.0.7
     */
    private function makePropertyPrivate(Property $property): void
    {
        $property->flags &= ~Modifiers::VISIBILITY_MASK;
        $property->flags &= ~(Modifiers::PUBLIC_SET | Modifiers::PROTECTED_SET | Modifiers::PRIVATE_SET);
        $property->flags |= Modifiers::PRIVATE;
    }

    /**
     * Checks whether a property fetch targets $this and optionally a specific property.
     *
     * @since 1.0.7
     */
    private function isSelfPropertyFetch(PropertyFetch $propertyFetch, ?string $propertyName = null): bool
    {
        if (! $propertyFetch->var instanceof Variable || $propertyFetch->var->name !== 'this') {
            return false;
        }

        if (! $propertyFetch->name instanceof Identifier) {
            return false;
        }

        if ($propertyName === null) {
            return true;
        }

        return $propertyFetch->name->toString() === $propertyName;
    }

    /**
     * Gets the normalized hook name.
     *
     * @since 1.0.7
     */
    private function getHookName(PropertyHook $hook): string
    {
        return $hook->name->toLowerString();
    }

    /**
     * Creates the downgraded getter method name.
     *
     * @since 1.0.7
     */
    private function createGetterMethodName(string $propertyName): string
    {
        return 'get' . ucfirst($propertyName);
    }

    /**
     * Creates the downgraded setter method name.
     *
     * @since 1.0.7
     */
    private function createSetterMethodName(string $propertyName): string
    {
        return 'set' . ucfirst($propertyName);
    }
}
