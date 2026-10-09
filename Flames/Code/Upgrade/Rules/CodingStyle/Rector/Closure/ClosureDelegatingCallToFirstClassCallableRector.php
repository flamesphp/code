<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\Closure;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VariadicPlaceholder;
use Flames\Code\Upgrade\Rules\CodingStyle\Guard\ArrowFunctionAndClosureFirstClassCallableGuard;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
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
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall
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
