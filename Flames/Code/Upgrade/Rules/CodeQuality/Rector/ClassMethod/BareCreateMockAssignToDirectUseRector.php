<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ClosureUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\AssertMethodAnalyzer;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\AssignedMocksCollector;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeFinder\VariableFinder;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\BareCreateMockAssignToDirectUseRectorTest
 */
final class BareCreateMockAssignToDirectUseRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly AssignedMocksCollector $assignedMocksCollector, private readonly BetterNodeFinder $betterNodeFinder, private readonly VariableFinder $variableFinder, private readonly AssertMethodAnalyzer $assertMethodAnalyzer)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add explicit instance assert between above nullable object pass', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $someObject = $this->createMock(SomeClass::class);
        $this->process($someObject);
    }


    private function process(SomeClass $someObject): void
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $this->process($this->createMock(SomeClass::class));
    }

    private function process(SomeClass $someObject): void
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
        return [ClassMethod::class, Foreach_::class];
    }
    /**
     * @param ClassMethod|Foreach_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        if ($node->stmts === null || count($node->stmts) < 2) {
            return null;
        }
        $mockedClassesToVariableNames = $this->assignedMocksCollector->collect($node);
        if ($mockedClassesToVariableNames === []) {
            return null;
        }
        $hasChanged = \false;
        foreach (array_keys($mockedClassesToVariableNames) as $variableName) {
            // variable cannot be part of any method call
            if ($this->isVariableUsedAsPartOfMethodCall($node, $variableName)) {
                continue;
            }
            if ($this->isUsedMoreOftenThanInCallLikeArgs($node, $variableName)) {
                continue;
            }
            if ($this->isUsedInClosure($node, $variableName)) {
                continue;
            }
            if ($this->isUsedInAssertCall($node, $variableName)) {
                continue;
            }
            // 1. remove initial assign
            $variablesToMethodCalls = [];
            foreach ($node->stmts as $key => $stmt) {
                if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                    $assign = $stmt->expr;
                    $instanceArg = $this->assignedMocksCollector->matchCreateMockArgAssignedToVariable($assign);
                    if ($instanceArg instanceof Arg && $assign->var instanceof Variable && $this->isName($assign->var, $variableName)) {
                        // 1. remove assign
                        unset($node->stmts[$key]);
                        $hasChanged = \true;
                        $variablesToMethodCalls[$variableName] = $assign->expr;
                        continue;
                    }
                }
                // nothing to processy yet
                if ($variablesToMethodCalls === []) {
                    continue;
                }
                // 2. replace variable with call-like args of new instance
                /** @var CallLike[] $callLikes */
                $callLikes = $this->findCallLikes($stmt);
                foreach ($callLikes as $callLike) {
                    foreach ($callLike->getArgs() as $arg) {
                        if (!$arg->value instanceof Variable) {
                            continue;
                        }
                        if (!$this->isName($arg->value, $variableName)) {
                            continue;
                        }
                        if (!isset($variablesToMethodCalls[$variableName])) {
                            continue;
                        }
                        // 2. replace variable with call-like args
                        $arg->value = $variablesToMethodCalls[$variableName];
                    }
                }
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_ $stmtsAware
     */
    private function isVariableUsedAsPartOfMethodCall($stmtsAware, string $variableName): bool
    {
        /** @var MethodCall[] $methodCalls */
        $methodCalls = $this->betterNodeFinder->findInstancesOfScoped([$stmtsAware], [MethodCall::class]);
        foreach ($methodCalls as $methodCall) {
            if ($this->isName($methodCall->var, $variableName)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_ $stmtsAware
     */
    private function isUsedMoreOftenThanInCallLikeArgs($stmtsAware, string $variableName): bool
    {
        // get use count
        $foundVariables = $this->variableFinder->find($stmtsAware, $variableName);
        // found method call, static call or new arg-only usage
        $callLikeVariableUseCount = 0;
        /** @var CallLike[] $callLikes */
        $callLikes = $this->findCallLikes($stmtsAware);
        foreach ($callLikes as $callLike) {
            foreach ($callLike->getArgs() as $arg) {
                if (!$arg->value instanceof Variable) {
                    continue;
                }
                if (!$this->isName($arg->value, $variableName)) {
                    continue;
                }
                ++$callLikeVariableUseCount;
            }
        }
        return $callLikeVariableUseCount > 1 || count($foundVariables) - 1 > $callLikeVariableUseCount;
    }
    /**
     * @return CallLike[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt $node
     */
    private function findCallLikes($node): array
    {
        $callLikes = $this->betterNodeFinder->findInstancesOfScoped([$node], [MethodCall::class, StaticCall::class, New_::class]);
        return array_filter($callLikes, fn(CallLike $callLike): bool => !$callLike->isFirstClassCallable());
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_ $stmtsAware
     */
    private function isUsedInClosure($stmtsAware, string $variableName): bool
    {
        /** @var Node\ClosureUse[] $uses */
        $uses = $this->betterNodeFinder->findInstancesOf([$stmtsAware], [ClosureUse::class]);
        foreach ($uses as $use) {
            if ($this->isName($use->var, $variableName)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_ $stmtsAware
     */
    private function isUsedInAssertCall($stmtsAware, string $variableName): bool
    {
        /** @var StaticCall[]|MethodCall[] $calls */
        $calls = $this->betterNodeFinder->findInstancesOfScoped([$stmtsAware], [MethodCall::class, StaticCall::class]);
        $assertCalls = [];
        foreach ($calls as $call) {
            if (!$this->assertMethodAnalyzer->detectTestCaseCall($call)) {
                continue;
            }
            $assertCalls[] = $call;
        }
        foreach ($assertCalls as $assertCall) {
            foreach ($assertCall->getArgs() as $assertCallArg) {
                if (!$assertCallArg->value instanceof Variable) {
                    continue;
                }
                if ($this->isName($assertCallArg->value, $variableName)) {
                    return \true;
                }
            }
        }
        return \false;
    }
}
