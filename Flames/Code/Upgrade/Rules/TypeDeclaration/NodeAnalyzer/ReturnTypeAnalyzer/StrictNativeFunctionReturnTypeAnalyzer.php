<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnTypeAnalyzer;

use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnAnalyzer;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnFilter\ExclusiveNativeCallLikeReturnMatcher;
final readonly class StrictNativeFunctionReturnTypeAnalyzer
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private ExclusiveNativeCallLikeReturnMatcher $exclusiveNativeCallLikeReturnMatcher, private ReturnAnalyzer $returnAnalyzer)
    {
    }
    /**
     * @return CallLike[]|null
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function matchAlwaysReturnNativeCallLikes($functionLike): ?array
    {
        if ($functionLike->stmts === null) {
            return null;
        }
        $returns = $this->betterNodeFinder->findReturnsScoped($functionLike);
        if (!$this->returnAnalyzer->hasOnlyReturnWithExpr($functionLike, $returns)) {
            return null;
        }
        return $this->exclusiveNativeCallLikeReturnMatcher->match($returns);
    }
}
