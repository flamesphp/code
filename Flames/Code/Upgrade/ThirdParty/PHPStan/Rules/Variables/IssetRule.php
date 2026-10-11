<?php

declare (strict_types=1);
namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\IssetExpressionNode;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
/**
 * @implements Rule<IssetExpressionNode>
 */
#[RegisteredRule(1)]
final class IssetRule implements Rule
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
        return IssetExpressionNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $messages = [];
        foreach ($node->varResults as $varResult) {
            $error = $this->issetCheck->check($varResult, $scope, 'in isset()', 'isset', static function (Type $type): ?string {
                $isNull = $type->isNull();
                if ($isNull->maybe()) {
                    return null;
                }
                if ($isNull->yes()) {
                    return 'is always null';
                }
                return 'is not nullable';
            });
            if ($error === null) {
                $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $varResult->getExpr());
                continue;
            }
            if ($scope->isInTrait()) {
                // IssetCheck's message already distinguishes the possible outcomes,
                // so the contexts only need to be told apart by error/no error.
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $varResult->getExpr(), \true, $error);
                continue;
            }
            $messages[] = $error;
        }
        return $messages;
    }
}
