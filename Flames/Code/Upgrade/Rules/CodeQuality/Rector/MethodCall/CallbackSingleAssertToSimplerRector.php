<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\CallbackSingleAssertToSimplerRectorTest
 */
final class CallbackSingleAssertToSimplerRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replaces use of with, callback and sole assertSame() to simple equalTo() matcher', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeClass extends TestCase
{
    public function test()
    {
        $builder->expects($this->exactly(2))
            ->method('add')
            ->with($this->callback(function ($type): bool {
                $this->assertSame(TextType::class, $type);

                return true;
            }));
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeClass extends TestCase
{
    public function test()
    {
        $builder->expects($this->exactly(2))
            ->method('add')
            ->with($this->equalTo(TextType::class));
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!$this->isName($node->name, 'with')) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getArgs() as $arg) {
            $expectedExpr = $this->matchCallbackSoleAssertSameExpected($arg->value);
            if (!$expectedExpr instanceof Expr) {
                continue;
            }
            $arg->value = $this->nodeFactory->createMethodCall('this', 'equalTo', [$expectedExpr]);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function matchCallbackSoleAssertSameExpected(Expr $expr): ?Expr
    {
        if (!$expr instanceof MethodCall || !$this->isName($expr->name, 'callback')) {
            return null;
        }
        $callbackArgs = $expr->getArgs();
        if ($callbackArgs === []) {
            return null;
        }
        $innerClosure = $callbackArgs[0]->value;
        if (!$innerClosure instanceof Closure) {
            return null;
        }
        // skip closures capturing by reference, as the referenced value is likely modified above
        foreach ($innerClosure->uses as $use) {
            if ($use->byRef) {
                return null;
            }
        }
        // sole assertSame() expression, optionally followed by "return true;"
        $closureStmts = $innerClosure->getStmts();
        if (count($closureStmts) === 2) {
            [$firstStmt, $secondStmt] = $closureStmts;
            if (!$secondStmt instanceof Return_ || !$this->isTrueReturn($secondStmt)) {
                return null;
            }
        } elseif (count($closureStmts) === 1) {
            $firstStmt = $closureStmts[0];
        } else {
            return null;
        }
        if (!$firstStmt instanceof Expression) {
            return null;
        }
        $firstStmtExpr = $firstStmt->expr;
        if (!$firstStmtExpr instanceof MethodCall || !$this->isName($firstStmtExpr->name, 'assertSame')) {
            return null;
        }
        $assertSameArgs = $firstStmtExpr->getArgs();
        if (count($assertSameArgs) !== 2) {
            return null;
        }
        $firstValue = $assertSameArgs[0]->value;
        $secondValue = $assertSameArgs[1]->value;
        // one side must be the whole closure parameter; the other side is the expected value
        // nested access (e.g. $args['label']) is skipped, as equalTo() matches the whole argument
        if ($this->isClosureSoleParam($innerClosure, $secondValue)) {
            return $firstValue;
        }
        if ($this->isClosureSoleParam($innerClosure, $firstValue)) {
            return $secondValue;
        }
        return null;
    }
    private function isClosureSoleParam(Closure $closure, Expr $expr): bool
    {
        if (count($closure->params) !== 1) {
            return \false;
        }
        if (!$expr instanceof Variable) {
            return \false;
        }
        $soleParam = $closure->params[0];
        if (!$soleParam->var instanceof Variable) {
            return \false;
        }
        return $this->nodeComparator->areNodesEqual($soleParam->var, $expr);
    }
    private function isTrueReturn(Return_ $return): bool
    {
        return $return->expr instanceof ConstFetch && $this->isName($return->expr, 'true');
    }
}
