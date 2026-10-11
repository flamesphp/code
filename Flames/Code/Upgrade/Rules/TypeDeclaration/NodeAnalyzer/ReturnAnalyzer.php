<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\SilentVoidResolver;
final readonly class ReturnAnalyzer
{
    public function __construct(private SilentVoidResolver $silentVoidResolver)
    {
    }
    /**
     * @param Return_[] $returns
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function hasOnlyReturnWithExpr($functionLike, array $returns): bool
    {
        if ($functionLike->stmts === null) {
            return \false;
        }
        // void or combined with yield/yield from
        if ($returns === []) {
            return \false;
        }
        // possible void
        foreach ($returns as $return) {
            if (!$return->expr instanceof Expr) {
                return \false;
            }
        }
        // possible silent void
        return !$this->silentVoidResolver->hasSilentVoid($functionLike);
    }
}
