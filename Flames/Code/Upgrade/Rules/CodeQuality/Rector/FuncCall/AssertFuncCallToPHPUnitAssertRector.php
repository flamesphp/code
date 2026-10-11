<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Bool_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\PHPUnit\Enum\AssertMethod;
use Flames\Code\Upgrade\PHPUnit\Enum\BehatClassName;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\AssertFuncCallToPHPUnitAssertRectorTest
 */
final class AssertFuncCallToPHPUnitAssertRector extends AbstractRector
{
    public function __construct(private readonly ValueResolver $valueResolver, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turns assert() calls in tests to PHPUnit assert method alternative', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeClass extends TestCase
{
    public function test()
    {
        $value = 1000;
        assert($value === 1000, 'message');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeClass extends TestCase
{
    public function test()
    {
        $value = 1000;

        $this->assertSame(1000, $value, "message")
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return class-string[]
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Class_
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node) && !$this->isBehatContext($node)) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if ($classMethod->stmts === null) {
                continue;
            }
            $useStaticAssert = $classMethod->isStatic() ?: $this->isBehatContext($node);
            $this->traverseNodesWithCallable($classMethod->stmts, function (Node $node) use (&$useStaticAssert, &$hasChanged) {
                if ($node instanceof Closure && $node->static) {
                    $useStaticAssert = \true;
                    return null;
                }
                // assert() inside an anonymous class has no $this of the test case
                if ($node instanceof Class_ && $node->isAnonymous()) {
                    $useStaticAssert = \true;
                    return null;
                }
                if (!$node instanceof FuncCall) {
                    return null;
                }
                if ($node->isFirstClassCallable()) {
                    return null;
                }
                if (!$this->isName($node, 'assert')) {
                    return null;
                }
                $comparedExpr = $node->getArgs()[0]->value;
                if ($comparedExpr instanceof Equal) {
                    $methodName = AssertMethod::ASSERT_EQUALS;
                    $exprs = [$comparedExpr->right, $comparedExpr->left];
                } elseif ($comparedExpr instanceof Identical) {
                    $methodName = AssertMethod::ASSERT_SAME;
                    $exprs = [$comparedExpr->right, $comparedExpr->left];
                } elseif ($comparedExpr instanceof NotIdentical) {
                    if ($this->valueResolver->isNull($comparedExpr->right)) {
                        $methodName = 'assertNotNull';
                        $exprs = [$comparedExpr->left];
                    } else {
                        return null;
                    }
                } elseif ($comparedExpr instanceof Bool_) {
                    $methodName = 'assertTrue';
                    $exprs = [$comparedExpr];
                } elseif ($comparedExpr instanceof FuncCall) {
                    if ($this->isName($comparedExpr, 'method_exists')) {
                        $methodName = 'assertTrue';
                        $exprs = [$comparedExpr];
                    } else {
                        return null;
                    }
                } elseif ($comparedExpr instanceof Instanceof_) {
                    // outside TestCase
                    $methodName = 'assertInstanceOf';
                    $exprs = [];
                    if ($comparedExpr->class instanceof FullyQualified) {
                        $classConstFetch = new ClassConstFetch($comparedExpr->class, 'class');
                        $exprs[] = $classConstFetch;
                    } else {
                        return null;
                    }
                    $exprs[] = $comparedExpr->expr;
                } else {
                    return null;
                }
                // is there a comment message
                if (isset($node->getArgs()[1])) {
                    $exprs[] = $node->getArgs()[1]->value;
                }
                $hasChanged = \true;
                return $this->createAssertCall($methodName, $exprs, $useStaticAssert);
            });
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function isBehatContext(Class_ $class): bool
    {
        $scope = ScopeFetcher::fetch($class);
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        // special case with static call
        return $classReflection->is(BehatClassName::CONTEXT);
    }
    /**
     * @param Expr[] $exprs
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall
     */
    private function createAssertCall(string $methodName, array $exprs, bool $useStaticAssert)
    {
        $args = [];
        foreach ($exprs as $expr) {
            $args[] = new Arg($expr);
        }
        if ($useStaticAssert) {
            $assertFullyQualified = new FullyQualified(PHPUnitClassName::ASSERT);
            return new StaticCall($assertFullyQualified, $methodName, $args);
        }
        return new MethodCall(new Variable('this'), $methodName, $args);
    }
}
