<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Expression;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRectorTest
 */
final class DecorateWillReturnMapWithExpectsMockRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Decorate willReturnMap() calls with expects on the mock object if missing', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class SomeTest extends TestCase
{
    private MockObject $someMock;

    protected function setUp(): void
    {
        $this->someMock = $this->createMock(SomeClass::class);

        $this->someMock->method("someMethod")
            ->willReturnMap([
                ["arg1", "arg2", "result1"],
                ["arg3", "arg4", "result2"],
            ]);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class SomeTest extends TestCase
{
    private MockObject $someMock;

    protected function setUp(): void
    {
        $this->someMock = $this->createMock(SomeClass::class);

        $this->someMock->expects($this->atLeast(2))
            ->method("someMethod")
            ->willReturnMap([
                ["arg1", "arg2", "result1"],
                ["arg3", "arg4", "result2"],
            ]);
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     * @return ClassMethod|null
     */
    public function refactor(Node $node)
    {
        // allowed as can be flexible
        if ($this->isName($node, MethodName::SET_UP)) {
            return null;
        }
        // only decorate inside test methods, skip helpers and other non-test methods
        if (!$this->testsNodeAnalyzer->isTestClassMethod($node)) {
            return null;
        }
        $hasChanged = \false;
        /** @var Expression[] $expressions */
        $expressions = $this->betterNodeFinder->findInstancesOf($node, [Expression::class]);
        foreach ($expressions as $expression) {
            if ($this->refactorExpression($expression)) {
                $hasChanged = \true;
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function refactorExpression(Expression $expression): bool
    {
        if (!$expression->expr instanceof MethodCall) {
            return \false;
        }
        $methodCall = $expression->expr;
        if (!$this->isName($methodCall->name, 'willReturnMap')) {
            return \false;
        }
        $topmostCall = $this->resolveTopmostCall($methodCall);
        // already covered
        if ($this->isName($topmostCall->name, 'expects')) {
            return \false;
        }
        if (!$this->isObjectType($topmostCall->var, new ObjectType(PHPUnitClassName::MOCK_OBJECT))) {
            return \false;
        }
        if ($methodCall->isFirstClassCallable()) {
            return \false;
        }
        // count values in will map arg
        $willReturnMapArg = $methodCall->getArgs()[0] ?? null;
        if (!$willReturnMapArg instanceof Arg) {
            return \false;
        }
        if (!$willReturnMapArg->value instanceof Array_) {
            return \false;
        }
        $array = $willReturnMapArg->value;
        $mapCount = count($array->items);
        $topmostCall->var = new MethodCall($topmostCall->var, new Identifier('expects'), [new Arg(new MethodCall(new Variable('this'), new Identifier('atLeast'), [new Arg(new Int_($mapCount))]))]);
        return \true;
    }
    private function resolveTopmostCall(MethodCall $methodCall): MethodCall
    {
        $currentCall = $methodCall;
        while ($currentCall->var instanceof MethodCall) {
            $currentCall = $currentCall->var;
        }
        return $currentCall;
    }
}
