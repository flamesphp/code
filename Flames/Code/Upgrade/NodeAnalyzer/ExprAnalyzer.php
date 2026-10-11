<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Greater;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\LogicalOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\LogicalXor;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Smaller;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BitwiseNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Bool_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Clone_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Empty_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ErrorSuppress;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Eval_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Exit_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Include_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Isset_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Print_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Throw_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryMinus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryPlus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\YieldFrom;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\InterpolatedString;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final readonly class ExprAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function isBoolExpr(Expr $expr): bool
    {
        return $expr instanceof BooleanNot || $expr instanceof Empty_ || $expr instanceof Isset_ || $expr instanceof Instanceof_ || $expr instanceof Bool_ || $expr instanceof Equal || $expr instanceof NotEqual || $expr instanceof Identical || $expr instanceof NotIdentical || $expr instanceof Greater || $expr instanceof GreaterOrEqual || $expr instanceof Smaller || $expr instanceof SmallerOrEqual || $expr instanceof BooleanAnd || $expr instanceof BooleanOr || $expr instanceof LogicalAnd || $expr instanceof LogicalOr || $expr instanceof LogicalXor;
    }
    public function isCallLikeReturnNativeBool(Expr $expr): bool
    {
        if (!$expr instanceof CallLike) {
            return \false;
        }
        $scope = $expr->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return \false;
        }
        $nativeType = $scope->getNativeType($expr);
        return $nativeType->isBoolean()->yes();
    }
    /**
     * Verify that Expr has ->expr property that can be wrapped by parentheses
     */
    public function isExprWithExprPropertyWrappable(Node $node): bool
    {
        if (!$node instanceof Expr) {
            return \false;
        }
        // ensure only verify on reprint, using token start verification is more reliable for its check
        if ($node->getStartTokenPos() > 0) {
            return \false;
        }
        if ($node instanceof Cast || $node instanceof YieldFrom || $node instanceof UnaryMinus || $node instanceof UnaryPlus || $node instanceof Throw_ || $node instanceof Empty_ || $node instanceof BooleanNot || $node instanceof Clone_ || $node instanceof ErrorSuppress || $node instanceof BitwiseNot || $node instanceof Eval_ || $node instanceof Print_ || $node instanceof Exit_ || $node instanceof Include_ || $node instanceof Instanceof_) {
            return $node->expr instanceof BinaryOp;
        }
        return \false;
    }
    public function isNonTypedFromParam(Expr $expr): bool
    {
        if (!$expr instanceof Variable) {
            return \false;
        }
        $scope = $expr->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            // uncertainty when scope not yet filled/overlapped on just refactored
            return \true;
        }
        $nativeType = $scope->getNativeType($expr);
        $type = $scope->getType($expr);
        if ($nativeType instanceof MixedType && !$nativeType->isExplicitMixed() || $nativeType instanceof MixedType && !$type instanceof MixedType) {
            return \true;
        }
        if ($nativeType instanceof ObjectWithoutClassType && !$type instanceof ObjectWithoutClassType) {
            return \true;
        }
        if (!$scope->hasVariableType((string) $this->nodeNameResolver->getName($expr))->yes()) {
            return \true;
        }
        if ($nativeType instanceof UnionType && !$nativeType->equals($type)) {
            return \true;
        }
        if (!$nativeType->isSuperTypeOf($type)->yes()) {
            return \true;
        }
        $definedVariables = $scope->getDefinedVariables();
        foreach ($definedVariables as $definedVariable) {
            $variableType = $scope->getVariableType($definedVariable);
            if ($variableType instanceof ConstantStringType && in_array($variableType->getValue(), $definedVariables, \true)) {
                return \true;
            }
        }
        return \false;
    }
    public function isDynamicExpr(Expr $expr): bool
    {
        // Unwrap UnaryPlus and UnaryMinus
        if ($expr instanceof UnaryPlus || $expr instanceof UnaryMinus) {
            $expr = $expr->expr;
        }
        if ($expr instanceof Array_) {
            return $this->isDynamicArray($expr);
        }
        if ($expr instanceof Scalar) {
            // string interpolation is true, otherwise false
            return $expr instanceof InterpolatedString;
        }
        return !$this->isAllowedConstFetchOrClassConstFetch($expr);
    }
    public function isDynamicArray(Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if (!$item instanceof ArrayItem) {
                continue;
            }
            if (!$this->isAllowedArrayKey($item->key)) {
                return \true;
            }
            if (!$this->isAllowedArrayValue($item->value)) {
                return \true;
            }
        }
        return \false;
    }
    private function isAllowedConstFetchOrClassConstFetch(Expr $expr): bool
    {
        if ($expr instanceof ConstFetch) {
            return \true;
        }
        if ($expr instanceof ClassConstFetch) {
            if (!$expr->class instanceof Name) {
                return \false;
            }
            if (!$expr->name instanceof Identifier) {
                return \false;
            }
            // static::class cannot be used for compile-time class name resolution
            return $expr->class->toString() !== ObjectReference::STATIC;
        }
        return \false;
    }
    private function isAllowedArrayKey(?Expr $expr): bool
    {
        if (!$expr instanceof Expr) {
            return \true;
        }
        if ($expr instanceof String_) {
            return \true;
        }
        return $expr instanceof Int_;
    }
    private function isAllowedArrayValue(Expr $expr): bool
    {
        if ($expr instanceof Array_) {
            return !$this->isDynamicArray($expr);
        }
        return !$this->isDynamicExpr($expr);
    }
}
