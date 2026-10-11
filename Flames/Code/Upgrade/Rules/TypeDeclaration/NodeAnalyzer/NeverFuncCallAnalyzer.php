<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Type\NeverType;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
final readonly class NeverFuncCallAnalyzer
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function hasNeverFuncCall($functionLike): bool
    {
        $found = array_any((array) $functionLike->stmts, fn($stmt) => $this->isWithNeverTypeExpr($stmt));
        return $found;
    }
    public function isWithNeverTypeExpr(Stmt $stmt, bool $withNativeNeverType = \true): bool
    {
        if ($stmt instanceof Expression) {
            $stmt = $stmt->expr;
        }
        if ($stmt instanceof Stmt) {
            return \false;
        }
        $stmtType = $withNativeNeverType ? $this->nodeTypeResolver->getNativeType($stmt) : $this->nodeTypeResolver->getType($stmt);
        return $stmtType instanceof NeverType;
    }
}
