<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Type\NeverType;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
final readonly class NeverFuncCallAnalyzer
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Stmt\Function_ $functionLike
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
