<?php

declare (strict_types=1);
namespace PHPStan\Node\Printer;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanAndNode;
use PHPStan\Node\BooleanOrNode;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\CloneReinitializationExpr;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalForeachValueExpr;
use PHPStan\Node\Expr\ParameterVariableOriginalValueExpr;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\InstantiationCallableNode;
use PHPStan\Node\IssetExpr;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Type\VerbosityLevel;
use function preg_match;
use function sprintf;
use function str_contains;
/**
 * @api
 */
#[AutowiredService(\null, \null, \PHPStan\Node\Printer\Printer::class)]
final class Printer extends Standard
{
    /**
     * Every construct the printer descends into comes back through here, and
     * each level of a chain is printed again in turn for its own expression
     * key, so a chain of depth N costs O(N^2). Remembering each expression's
     * printed form on its node makes the levels below it O(1) — and it is the
     * very same cache ExprPrinter fills, so a key printed once is never
     * rebuilt, wherever it was first reached from.
     *
     * Only the standalone form is remembered: at a lower precedence the
     * printer may parenthesise, and a form containing a newline was indented
     * for the level it was printed at, while expression keys are printed at
     * the top level.
     */
    #[Override]
    protected function p(Node $node, int $precedence = self::MAX_PRECEDENCE, int $lhsPrecedence = self::MAX_PRECEDENCE, bool $parentFormatPreserved = \false): string
    {
        if (!$node instanceof Expr || $precedence !== self::MAX_PRECEDENCE || $lhsPrecedence !== self::MAX_PRECEDENCE) {
            return parent::p($node, $precedence, $lhsPrecedence, $parentFormatPreserved);
        }
        // ExprPrinter::printExpr() caches a multi-line form too, printed at the top level - nested
        // here, it would need the current indentation
        $printed = $node->getAttribute(\PHPStan\Node\Printer\ExprPrinter::ATTRIBUTE_CACHE_KEY);
        if ($printed !== null && !str_contains($printed, "\n")) {
            return $printed;
        }
        $printed = parent::p($node, 1000, 1000, $parentFormatPreserved);
        if (!str_contains($printed, "\n")) {
            $node->setAttribute(\PHPStan\Node\Printer\ExprPrinter::ATTRIBUTE_CACHE_KEY, $printed);
        }
        return $printed;
    }
    /**
     * Normalize curly-brace member access with a constant string name to the
     * bareword form, so that e.g. `$obj->{'n'}` and `$obj->n` (or `$obj->{'n'}()`
     * and `$obj->n()`) produce identical expression keys and are treated as the
     * same member by the analyser.
     */
    #[Override]
    protected function pObjectProperty(Node $node): string
    {
        if ($node instanceof String_ && preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $node->value) === 1) {
            return $node->value;
        }
        return parent::pObjectProperty($node);
    }
    protected function pPHPStan_Node_TypeExpr(TypeExpr $expr): string
    {
        return sprintf('__phpstanType(%s)', $expr->exprType->describe(VerbosityLevel::precise()));
    }
    protected function pPHPStan_Node_NativeTypeExpr(NativeTypeExpr $expr): string
    {
        return sprintf('__phpstanNativeType(%s, %s)', $expr->phpdocType->describe(VerbosityLevel::precise()), $expr->nativeType->describe(VerbosityLevel::precise()));
    }
    protected function pPHPStan_Node_UnsetOffsetExpr(UnsetOffsetExpr $expr): string
    {
        return sprintf('__phpstanUnsetOffset(%s, %s)', $this->p($expr->var), $this->p($expr->dim));
    }
    protected function pPHPStan_Node_ExistingArrayDimFetch(ExistingArrayDimFetch $expr): string
    {
        return sprintf('__phpstanExistingArrayDimFetch(%s, %s)', $this->p($expr->var), $this->p($expr->dim));
    }
    protected function pPHPStan_Node_SetOffsetValueTypeExpr(SetOffsetValueTypeExpr $expr): string
    {
        return sprintf('__phpstanSetOffsetValueType(%s, %s, %s)', $this->p($expr->var), $expr->dim !== null ? $this->p($expr->dim) : 'null', $this->p($expr->value));
    }
    protected function pPHPStan_Node_SetExistingOffsetValueTypeExpr(SetExistingOffsetValueTypeExpr $expr): string
    {
        return sprintf('__phpstanSetExistingOffsetValueType(%s, %s, %s)', $this->p($expr->var), $this->p($expr->dim), $this->p($expr->value));
    }
    protected function pPHPStan_Node_AlwaysRememberedExpr(AlwaysRememberedExpr $expr): string
    {
        return sprintf('__phpstanRemembered(%s)', $this->p($expr->expr));
    }
    protected function pPHPStan_Node_PossiblyImpureCallExpr(PossiblyImpureCallExpr $expr): string
    {
        return sprintf('__phpstanPossiblyImpure(%s, %s)', $this->p($expr->callExpr), $this->p($expr->impactedExpr));
    }
    protected function pPHPStan_Node_PropertyInitializationExpr(PropertyInitializationExpr $expr): string
    {
        return sprintf('__phpstanPropertyInitialization(%s)', $expr->propertyName);
    }
    protected function pPHPStan_Node_CloneReinitializationExpr(CloneReinitializationExpr $expr): string
    {
        return sprintf('__phpstanCloneReinitialization(%s)', $expr->propertyName);
    }
    protected function pPHPStan_Node_ForeachValueByRefExpr(ForeachValueByRefExpr $expr): string
    {
        return sprintf('__phpstanForeachValueByRef(%s)', $this->p($expr->expr));
    }
    protected function pPHPStan_Node_ParameterVariableOriginalValueExpr(ParameterVariableOriginalValueExpr $expr): string
    {
        return sprintf('__phpstanParameterVariableOriginalValue(%s)', $expr->variableName);
    }
    protected function pPHPStan_Node_OriginalForeachKeyExpr(OriginalForeachKeyExpr $expr): string
    {
        return sprintf('__phpstanOriginalForeachKey(%s)', $expr->variableName);
    }
    protected function pPHPStan_Node_OriginalForeachValueExpr(OriginalForeachValueExpr $expr): string
    {
        return sprintf('__phpstanOriginalForeachValue(%s)', $expr->variableName);
    }
    protected function pPHPStan_Node_IntertwinedVariableByReferenceWithExpr(IntertwinedVariableByReferenceWithExpr $expr): string
    {
        return sprintf('__phpstanIntertwinedVariableByReference(%s, %s, %s)', $expr->variableName, $this->p($expr->expr), $this->p($expr->assignedExpr));
    }
    protected function pPHPStan_Node_IssetExpr(IssetExpr $expr): string
    {
        return sprintf('__phpstanIssetExpr(%s)', $this->p($expr->expr));
    }
    protected function pPHPStan_Node_BooleanOrNode(BooleanOrNode $expr): string
    {
        return sprintf('__phpstanBooleanOr(%s, %s)', $this->p($expr->originalNode->left), $this->p($expr->originalNode->right));
    }
    protected function pPHPStan_Node_BooleanAndNode(BooleanAndNode $expr): string
    {
        return sprintf('__phpstanBooleanAnd(%s, %s)', $this->p($expr->originalNode->left), $this->p($expr->originalNode->right));
    }
    protected function pPHPStan_Node_FunctionCallableNode(FunctionCallableNode $expr): string
    {
        return sprintf('__phpstanFunctionCallable(%s)', $this->p($expr->originalNode));
    }
    protected function pPHPStan_Node_MethodCallableNode(MethodCallableNode $expr): string
    {
        return sprintf('__phpstanMethodCallable(%s)', $this->p($expr->originalNode));
    }
    protected function pPHPStan_Node_StaticMethodCallableNode(StaticMethodCallableNode $expr): string
    {
        return sprintf('__phpstanStaticMethodCallable(%s)', $this->p($expr->originalNode));
    }
    protected function pPHPStan_Node_InstantiationCallableNode(InstantiationCallableNode $expr): string
    {
        return sprintf('__phpstanInstantiationCallable(%s)', $this->p($expr->originalNode));
    }
}
