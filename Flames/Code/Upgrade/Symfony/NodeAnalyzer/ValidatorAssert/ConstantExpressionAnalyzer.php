<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer\ValidatorAssert;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BitwiseNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryMinus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryPlus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Float_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\MagicConst;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VariadicPlaceholder;
/**
 * An attribute argument must be a constant expression, so anything evaluated at runtime - a closure, a method call,
 * a variable - makes the attribute a parse error.
 *
 * @see https://www.php.net/manual/en/language.attributes.syntax.php
 * @see \Flames\Code\Upgrade\Symfony\Tests\NodeAnalyzer\ValidatorAssert\ConstantExpressionAnalyzerTest
 */
final class ConstantExpressionAnalyzer
{
    /**
     * @param array<Arg|VariadicPlaceholder> $args
     */
    public function areArgsConstant(array $args): bool
    {
        foreach ($args as $arg) {
            // a first-class callable, e.g. new Assert\Callback(...)
            if (!$arg instanceof Arg) {
                return \false;
            }
            if (!$this->isConstant($arg->value)) {
                return \false;
            }
        }
        return \true;
    }
    private function isConstant(Expr $expr): bool
    {
        if ($expr instanceof String_ || $expr instanceof Int_ || $expr instanceof Float_ || $expr instanceof MagicConst) {
            return \true;
        }
        if ($expr instanceof ConstFetch) {
            return \true;
        }
        if ($expr instanceof ClassConstFetch) {
            return $expr->class instanceof Name;
        }
        if ($expr instanceof Array_) {
            return $this->areArrayItemsConstant($expr);
        }
        // new is allowed in an attribute argument since PHP 8.1, e.g. #[Assert\All(new Assert\NotBlank())]
        if ($expr instanceof New_) {
            return $expr->class instanceof Name && $this->areArgsConstant($expr->args);
        }
        if ($expr instanceof BinaryOp) {
            return $this->isConstant($expr->left) && $this->isConstant($expr->right);
        }
        if ($expr instanceof UnaryMinus || $expr instanceof UnaryPlus || $expr instanceof BitwiseNot || $expr instanceof BooleanNot) {
            return $this->isConstant($expr->expr);
        }
        if ($expr instanceof Ternary) {
            if (!$this->isConstant($expr->cond)) {
                return \false;
            }
            if ($expr->if instanceof Expr && !$this->isConstant($expr->if)) {
                return \false;
            }
            return $this->isConstant($expr->else);
        }
        if ($expr instanceof ArrayDimFetch) {
            if (!$expr->dim instanceof Expr) {
                return \false;
            }
            return $this->isConstant($expr->var) && $this->isConstant($expr->dim);
        }
        return \false;
    }
    private function areArrayItemsConstant(Array_ $array): bool
    {
        foreach ($array->items as $arrayItem) {
            if (!$arrayItem instanceof ArrayItem) {
                return \false;
            }
            if ($arrayItem->key instanceof Expr && !$this->isConstant($arrayItem->key)) {
                return \false;
            }
            if (!$this->isConstant($arrayItem->value)) {
                return \false;
            }
        }
        return \true;
    }
}
