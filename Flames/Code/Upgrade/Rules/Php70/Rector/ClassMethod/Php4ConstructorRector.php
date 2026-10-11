<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\NodeCollector\ScopeResolver\ParentClassScopeResolver;
use Flames\Code\Upgrade\Rules\Php70\NodeAnalyzer\MethodCallNameAnalyzer;
use Flames\Code\Upgrade\Rules\Php70\NodeAnalyzer\Php4ConstructorClassMethodAnalyzer;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php70\Rector\ClassMethod\Php4ConstructorRectorTest
 */
final class Php4ConstructorRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly Php4ConstructorClassMethodAnalyzer $php4ConstructorClassMethodAnalyzer, private readonly ParentClassScopeResolver $parentClassScopeResolver, private readonly MethodCallNameAnalyzer $methodCallNameAnalyzer)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_PHP4_CONSTRUCTOR;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change PHP 4 style constructor to `__construct`', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function SomeClass()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct()
    {
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_
    {
        $scope = ScopeFetcher::fetch($node);
        // catch only classes without namespace
        if ($scope->getNamespace() !== null) {
            return null;
        }
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        $className = $this->getName($node);
        if (!is_string($className)) {
            return null;
        }
        foreach ($node->stmts as $classStmtKey => $classStmt) {
            if (!$classStmt instanceof ClassMethod) {
                continue;
            }
            if (!$this->php4ConstructorClassMethodAnalyzer->detect($classStmt, $classReflection)) {
                continue;
            }
            $psr4ConstructorMethod = $classStmt;
            // process parent call references first
            $this->processClassMethodStatementsForParentConstructorCalls($psr4ConstructorMethod, $scope);
            // does it already have a __construct method?
            if (!$node->getMethod(MethodName::CONSTRUCT) instanceof ClassMethod) {
                $psr4ConstructorMethod->name = new Identifier(MethodName::CONSTRUCT);
            }
            foreach ((array) $psr4ConstructorMethod->stmts as $classMethodStmt) {
                if (!$classMethodStmt instanceof Expression) {
                    continue;
                }
                // remove delegating method
                if ($this->methodCallNameAnalyzer->isLocalMethodCallNamed($classMethodStmt->expr, MethodName::CONSTRUCT)) {
                    unset($node->stmts[$classStmtKey]);
                }
                if ($this->methodCallNameAnalyzer->isParentMethodCall($node, $classMethodStmt->expr)) {
                    /** @var MethodCall $expr */
                    $expr = $classMethodStmt->expr;
                    /** @var string $parentClassName */
                    $parentClassName = $this->getParentClassName($node);
                    $classMethodStmt->expr = new StaticCall(new FullyQualified($parentClassName), new Identifier(MethodName::CONSTRUCT), $expr->args);
                }
            }
            return $node;
        }
        return null;
    }
    private function processClassMethodStatementsForParentConstructorCalls(ClassMethod $classMethod, Scope $scope): void
    {
        foreach ((array) $classMethod->stmts as $methodStmt) {
            if (!$methodStmt instanceof Expression) {
                continue;
            }
            $methodStmt = $methodStmt->expr;
            if (!$methodStmt instanceof StaticCall) {
                continue;
            }
            $this->processParentPhp4ConstructCall($methodStmt, $scope);
        }
    }
    private function processParentPhp4ConstructCall(StaticCall $staticCall, Scope $scope): void
    {
        $parentClassReflection = $this->parentClassScopeResolver->resolveParentClassReflection($scope);
        // no parent class
        if (!$parentClassReflection instanceof ClassReflection) {
            return;
        }
        if (!$staticCall->class instanceof Name) {
            return;
        }
        // rename ParentClass
        if ($this->isName($staticCall->class, $parentClassReflection->getName())) {
            $staticCall->class = new Name(ObjectReference::PARENT);
        }
        if (!$this->isName($staticCall->class, ObjectReference::PARENT)) {
            return;
        }
        // it's not a parent PHP 4 constructor call
        if (!$this->isName($staticCall->name, $parentClassReflection->getName())) {
            return;
        }
        $staticCall->name = new Identifier(MethodName::CONSTRUCT);
    }
    private function getParentClassName(Class_ $class): ?string
    {
        if (!$class->extends instanceof Node) {
            return null;
        }
        return $class->extends->toString();
    }
}
