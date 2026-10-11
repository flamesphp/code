<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\LogicalOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Pipe;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BitwiseNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Clone_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Empty_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Isset_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryMinus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryPlus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class LivingCodeManipulator
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver, private ReflectionProvider $reflectionProvider)
    {
    }
    /**
     * @return Expr[]|mixed[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node|int|string|null $expr
     */
    public function keepLivingCodeFromExpr($expr): array
    {
        if (!$expr instanceof Expr) {
            return [];
        }
        if ($expr instanceof Closure || $expr instanceof Scalar || $expr instanceof ConstFetch) {
            return [];
        }
        if ($expr instanceof Clone_ && $this->hasCloneMagicMethod($expr)) {
            return [$expr];
        }
        if ($this->isNestedExpr($expr)) {
            return $this->keepLivingCodeFromExpr($expr->expr);
        }
        if ($expr instanceof Variable) {
            return $this->keepLivingCodeFromExpr($expr->name);
        }
        if ($expr instanceof PropertyFetch) {
            return array_merge($this->keepLivingCodeFromExpr($expr->var), $this->keepLivingCodeFromExpr($expr->name));
        }
        if ($expr instanceof ArrayDimFetch) {
            $type = $this->nodeTypeResolver->getType($expr->var);
            if ($type instanceof ObjectType) {
                $objectType = new ObjectType('ArrayAccess');
                if ($objectType->isSuperTypeOf($type)->yes()) {
                    return [$expr];
                }
            }
            return array_merge($this->keepLivingCodeFromExpr($expr->var), $this->keepLivingCodeFromExpr($expr->dim));
        }
        if ($expr instanceof ClassConstFetch || $expr instanceof StaticPropertyFetch) {
            return array_merge($this->keepLivingCodeFromExpr($expr->class), $this->keepLivingCodeFromExpr($expr->name));
        }
        if ($this->isBinaryOpWithoutChange($expr)) {
            /** @var BinaryOp $binaryOp */
            $binaryOp = $expr;
            return $this->processBinary($binaryOp);
        }
        if ($expr instanceof Instanceof_) {
            return array_merge($this->keepLivingCodeFromExpr($expr->expr), $this->keepLivingCodeFromExpr($expr->class));
        }
        if ($expr instanceof Isset_) {
            return $this->processIsset($expr);
        }
        return [$expr];
    }
    private function isNestedExpr(Expr $expr): bool
    {
        return $expr instanceof Cast || $expr instanceof Empty_ || $expr instanceof UnaryMinus || $expr instanceof UnaryPlus || $expr instanceof BitwiseNot || $expr instanceof BooleanNot || $expr instanceof Clone_;
    }
    private function hasCloneMagicMethod(Clone_ $clone): bool
    {
        $cloneObjectType = $this->nodeTypeResolver->getType($clone->expr);
        foreach ($cloneObjectType->getObjectClassNames() as $className) {
            if (!$this->reflectionProvider->hasClass($className)) {
                continue;
            }
            if ($this->reflectionProvider->getClass($className)->hasNativeMethod(MethodName::CLONE)) {
                return \true;
            }
        }
        return \false;
    }
    private function isBinaryOpWithoutChange(Expr $expr): bool
    {
        if (!$expr instanceof BinaryOp) {
            return \false;
        }
        return !($expr instanceof LogicalAnd || $expr instanceof BooleanAnd || $expr instanceof LogicalOr || $expr instanceof BooleanOr || $expr instanceof Coalesce || $expr instanceof Pipe);
    }
    /**
     * @return Expr[]
     */
    private function processBinary(BinaryOp $binaryOp): array
    {
        return array_merge($this->keepLivingCodeFromExpr($binaryOp->left), $this->keepLivingCodeFromExpr($binaryOp->right));
    }
    /**
     * @return mixed[]
     */
    private function processIsset(Isset_ $isset): array
    {
        $livingExprs = [];
        foreach ($isset->vars as $expr) {
            $livingExprs = array_merge($livingExprs, $this->keepLivingCodeFromExpr($expr));
        }
        return $livingExprs;
    }
}
