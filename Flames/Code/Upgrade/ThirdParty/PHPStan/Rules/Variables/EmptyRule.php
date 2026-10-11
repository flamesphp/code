<?php

declare (strict_types=1);
namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\EmptyExpressionNode;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
/**
 * @implements Rule<EmptyExpressionNode>
 */
#[RegisteredRule(1)]
final class EmptyRule implements Rule
{
    private IssetCheck $issetCheck;
    private ConstantConditionInTraitHelper $constantConditionInTraitHelper;
    public function __construct(IssetCheck $issetCheck, ConstantConditionInTraitHelper $constantConditionInTraitHelper)
    {
        $this->issetCheck = $issetCheck;
        $this->constantConditionInTraitHelper = $constantConditionInTraitHelper;
    }
    public function getNodeType(): string
    {
        return EmptyExpressionNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $exprResult = $node->exprResult;
        $error = $this->issetCheck->check($exprResult, $scope, 'in empty()', 'empty', static function (Type $type): ?string {
            $isNull = $type->isNull();
            if ($isNull->maybe()) {
                return null;
            }
            $isFalsey = $type->toBoolean()->isFalse();
            if ($isFalsey->maybe()) {
                return null;
            }
            if ($isNull->yes()) {
                if ($isFalsey->yes()) {
                    return 'is always falsy';
                }
                if ($isFalsey->no()) {
                    return 'is not falsy';
                }
                return 'is always null';
            }
            if ($isFalsey->yes()) {
                return 'is always falsy';
            }
            if ($isFalsey->no()) {
                return 'is not falsy';
            }
            return 'is not nullable';
        });
        if ($error === null) {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $exprResult->getExpr());
            return [];
        }
        if ($scope->isInTrait()) {
            // IssetCheck's message already distinguishes the possible outcomes,
            // so the contexts only need to be told apart by error/no error.
            $this->constantConditionInTraitHelper->emitError(self::class, $scope, $exprResult->getExpr(), \true, $error);
            return [];
        }
        return [$error];
    }
}
