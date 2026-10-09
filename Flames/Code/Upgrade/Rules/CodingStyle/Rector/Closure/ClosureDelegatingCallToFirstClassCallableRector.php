<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\Closure;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\VariadicPlaceholder;
use Flames\Code\Upgrade\Rules\CodingStyle\Guard\ArrowFunctionAndClosureFirstClassCallableGuard;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRectorTest
 */
final class ClosureDelegatingCallToFirstClassCallableRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ArrowFunctionAndClosureFirstClassCallableGuard $arrowFunctionAndClosureFirstClassCallableGuard)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Convert closure with sole nested call to first class callable', [new CodeSample(<<<'CODE_SAMPLE'
function ($parameter) {
    return AnotherClass::someMethod($parameter);
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
AnotherClass::someMethod(...);
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Closure::class];
    }
    /**
     * @param Closure $node
     * @return null|\PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall
     */
    public function refactor(Node $node)
    {
        // must have exactly 1 stmt with Return_
        if (count($node->stmts) !== 1 || !$node->stmts[0] instanceof Return_) {
            return null;
        }
        $callLike = $node->stmts[0]->expr;
        if (!$callLike instanceof FuncCall && !$callLike instanceof MethodCall && !$callLike instanceof StaticCall) {
            return null;
        }
        // dynamic name? skip
        if ($callLike->name instanceof Expr) {
            return null;
        }
        if ($this->arrowFunctionAndClosureFirstClassCallableGuard->shouldSkip($node, $callLike, ScopeFetcher::fetch($node))) {
            return null;
        }
        $callLike->args = [new VariadicPlaceholder()];
        return $callLike;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::FIRST_CLASS_CALLABLE_SYNTAX;
    }
}
