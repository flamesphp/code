<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnAnalyzer;
final readonly class ReturnNodeFinder
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private ReturnAnalyzer $returnAnalyzer)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function findOnlyReturnWithExpr($functionLike): ?Return_
    {
        $returnsScoped = $this->betterNodeFinder->findReturnsScoped($functionLike);
        if (!$this->returnAnalyzer->hasOnlyReturnWithExpr($functionLike, $returnsScoped)) {
            return null;
        }
        if (count($returnsScoped) !== 1) {
            return null;
        }
        return $returnsScoped[0];
    }
}
