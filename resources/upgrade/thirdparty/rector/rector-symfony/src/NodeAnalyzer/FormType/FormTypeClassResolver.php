<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer\FormType;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PHPStan\Type\TypeWithClassName;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
final readonly class FormTypeClassResolver
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function resolveFromExpr(Expr $expr): ?string
    {
        if ($expr instanceof New_) {
            // we can only process direct name
            return $this->nodeNameResolver->getName($expr->class);
        }
        $exprType = $this->nodeTypeResolver->getType($expr);
        if ($exprType instanceof TypeWithClassName) {
            return $exprType->getClassName();
        }
        return null;
    }
}
