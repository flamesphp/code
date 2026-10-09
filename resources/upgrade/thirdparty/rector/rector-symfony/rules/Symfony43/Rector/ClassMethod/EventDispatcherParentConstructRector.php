<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony43\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Symfony43\Rector\ClassMethod\EventDispatcherParentConstructRectorTest
 */
final class EventDispatcherParentConstructRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/event-dispatcher', '>=4.3');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Removes parent construct method call in EventDispatcher class', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\EventDispatcher\EventDispatcher;

final class SomeEventDispatcher extends EventDispatcher
{
    public function __construct()
    {
        $value = 1000;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\EventDispatcher\EventDispatcher;

final class SomeEventDispatcher extends EventDispatcher
{
    public function __construct()
    {
        $value = 1000;
        parent::__construct();
    }
}

CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        $scope = ScopeFetcher::fetch($node);
        if (!$scope->isInClass()) {
            return null;
        }
        if (!$this->isName($node->name, MethodName::CONSTRUCT)) {
            return null;
        }
        $classReflection = $scope->getClassReflection();
        if (!$classReflection->is('Symfony\Contracts\EventDispatcher\EventDispatcherInterface')) {
            return null;
        }
        if (!$classReflection->getParentClass() instanceof ClassReflection) {
            return null;
        }
        if ($this->hasParentCallOfMethod($node, MethodName::CONSTRUCT)) {
            return null;
        }
        $node->stmts[] = $this->createParentStaticCall(MethodName::CONSTRUCT);
        return $node;
    }
    private function createParentStaticCall(string $method): Expression
    {
        $staticCall = $this->nodeFactory->createStaticCall(ObjectReference::PARENT, $method);
        return new Expression($staticCall);
    }
    /**
     * Looks for "parent::<methodName>"
     */
    private function hasParentCallOfMethod(ClassMethod $classMethod, string $method): bool
    {
        return (bool) $this->betterNodeFinder->findFirst((array) $classMethod->stmts, function (Node $node) use ($method): bool {
            if (!$node instanceof StaticCall) {
                return \false;
            }
            if (!$this->isName($node->class, ObjectReference::PARENT)) {
                return \false;
            }
            return $this->isName($node->name, $method);
        });
    }
}
