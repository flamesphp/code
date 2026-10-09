<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\VariableAndDimFetch;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
final readonly class AssertHasKeyMatcher
{
    public function __construct(private TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function match(Expression $expression): ?VariableAndDimFetch
    {
        if (!$expression->expr instanceof StaticCall && !$expression->expr instanceof MethodCall) {
            return null;
        }
        if (!$this->testsNodeAnalyzer->isAssertMethodCallName($expression->expr, 'assertArrayHasKey')) {
            return null;
        }
        $assertHasKeyCall = $expression->expr;
        $assertedArg = $assertHasKeyCall->getArgs()[0];
        $assertedExpr = $assertedArg->value;
        if (!$assertedExpr instanceof String_) {
            return null;
        }
        $variableArg = $assertHasKeyCall->getArgs()[1];
        $variableExpr = $variableArg->value;
        if (!$variableExpr instanceof Variable) {
            return null;
        }
        return new VariableAndDimFetch($variableExpr, $assertedExpr);
    }
}
