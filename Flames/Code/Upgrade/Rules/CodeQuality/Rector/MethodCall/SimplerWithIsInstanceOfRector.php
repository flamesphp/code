<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\SimplerWithIsInstanceOfRectorTest
 */
final class SimplerWithIsInstanceOfRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replaces use of with, callable and instance assert to simple isInstanceOf() method', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase

final class SomeClass extends TestCase
{
    public function test()
    {
        $someMock = $this->createMock(SomeClass::class)
            ->method('someMethod')
            ->with($this->callable(function ($arg): bool {
                return $arg instanceof SomeType;
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
        $someMock = $this->createMock(SomeClass::class)
            ->method('someMethod')
            ->with($this->isInstanceOf(SomeType::class));
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
            $instanceCheckedClassName = $this->matchCallbackSoleInstanceofCheckClassName($arg->value);
            if (!$instanceCheckedClassName instanceof Node) {
                continue;
            }
            // convert name to expr
            if ($instanceCheckedClassName instanceof Name) {
                $instanceCheckedClassName = $this->nodeFactory->createClassConstFetch($instanceCheckedClassName->toString(), 'class');
            }
            $arg->value = $this->nodeFactory->createMethodCall('this', 'isInstanceOf', [$instanceCheckedClassName]);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node|null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name
     */
    private function matchCallbackSoleInstanceofCheckClassName(Expr $expr)
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
        return $this->matchSoleInstanceofCheckClassName($innerClosure);
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node|null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name
     */
    private function matchSoleInstanceofCheckClassName(Closure $innerClosure)
    {
        // return + instancecheck only
        $innerClosureStmts = $innerClosure->getStmts();
        if (count($innerClosureStmts) === 2) {
            if (!$innerClosureStmts[1] instanceof Return_) {
                return null;
            }
            $firstStmt = $innerClosureStmts[0];
            if (!$firstStmt instanceof Expression) {
                return null;
            }
            $firstStmtExpr = $firstStmt->expr;
            if (!$firstStmtExpr instanceof MethodCall) {
                return null;
            }
            if (!$this->isName($firstStmtExpr->name, 'assertInstanceOf')) {
                return null;
            }
            return $firstStmtExpr->getArgs()[0]->value;
        }
        if (count($innerClosureStmts) === 1) {
            $onlyStmt = $innerClosureStmts[0];
            if (!$onlyStmt instanceof Return_) {
                return null;
            }
            $returnExpr = $onlyStmt->expr;
            if (!$returnExpr instanceof Instanceof_) {
                return null;
            }
            $instanceofExpr = $returnExpr;
            if (!$instanceofExpr->class instanceof Name) {
                return null;
            }
            return $instanceofExpr->class;
        }
        return null;
    }
}
