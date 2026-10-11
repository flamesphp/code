<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\LogicalXor;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;
/**
 * @implements Rule<LogicalXor>
 */
#[RegisteredRule(4)]
final class LogicalXorConstantConditionRule implements Rule
{
    private \PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper;
    private \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper;
    private \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper;
    private \PHPStan\Rules\Comparison\FunctionCallConstantConditionHelper $functionCallConstantConditionHelper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $reportAlwaysTrueInLastCondition;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(
        \PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper,
        \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper,
        \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper,
        \PHPStan\Rules\Comparison\FunctionCallConstantConditionHelper $functionCallConstantConditionHelper,
        #[AutowiredParameter]
        bool $treatPhpDocTypesAsCertain,
        #[AutowiredParameter]
        bool $reportAlwaysTrueInLastCondition,
        #[AutowiredParameter('%tips.treatPhpDocTypesAsCertain%')]
        bool $treatPhpDocTypesAsCertainTip
    )
    {
        $this->helper = $helper;
        $this->possiblyImpureTipHelper = $possiblyImpureTipHelper;
        $this->constantConditionInTraitHelper = $constantConditionInTraitHelper;
        $this->functionCallConstantConditionHelper = $functionCallConstantConditionHelper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return LogicalXor::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $errors = [];
        $isInTrait = $scope->isInTrait();
        $leftType = $this->helper->getBooleanType($scope, $node->left);
        if ($leftType instanceof ConstantBooleanType) {
            $addTipLeft = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->left, $ruleErrorBuilder);
                }
                $booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->left);
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->left, $ruleErrorBuilder);
                }
                if (!$this->treatPhpDocTypesAsCertainTip) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->left, $ruleErrorBuilder);
                }
                $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
                return $this->possiblyImpureTipHelper->addTip($scope, $node->left, $ruleErrorBuilder);
            };
            $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
            if (!$leftType->getValue() || $isLast !== \true || $this->reportAlwaysTrueInLastCondition) {
                $errorBuilder = $addTipLeft(RuleErrorBuilder::message(sprintf('Left side of xor is always %s.', $leftType->getValue() ? 'true' : 'false')))->identifier(sprintf('logicalXor.leftAlways%s', $leftType->getValue() ? 'True' : 'False'))->line($node->left->getStartLine());
                if ($leftType->getValue() && $isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
                    $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
                }
                $ruleError = $errorBuilder->build();
                if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->left)) {
                    $this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $node->left, $leftType->getValue(), $ruleError);
                } elseif ($isInTrait) {
                    $this->constantConditionInTraitHelper->emitError(self::class, $scope, $node->left, $leftType->getValue(), $ruleError);
                } else {
                    $errors[] = $ruleError;
                }
            } else {
                $this->emitNoError($scope, $node->left);
            }
        } else {
            $this->emitNoError($scope, $node->left);
        }
        $rightType = $this->helper->getBooleanType($scope, $node->right);
        if ($rightType instanceof ConstantBooleanType) {
            $addTipRight = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->right, $ruleErrorBuilder);
                }
                $booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->right);
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->right, $ruleErrorBuilder);
                }
                if (!$this->treatPhpDocTypesAsCertainTip) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->right, $ruleErrorBuilder);
                }
                $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
                return $this->possiblyImpureTipHelper->addTip($scope, $node->right, $ruleErrorBuilder);
            };
            $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
            if (!$rightType->getValue() || $isLast !== \true || $this->reportAlwaysTrueInLastCondition) {
                $errorBuilder = $addTipRight(RuleErrorBuilder::message(sprintf('Right side of xor is always %s.', $rightType->getValue() ? 'true' : 'false')))->identifier(sprintf('logicalXor.rightAlways%s', $rightType->getValue() ? 'True' : 'False'))->line($node->right->getStartLine());
                if ($rightType->getValue() && $isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
                    $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
                }
                $ruleError = $errorBuilder->build();
                if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->right)) {
                    $this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $node->right, $rightType->getValue(), $ruleError);
                } elseif ($isInTrait) {
                    $this->constantConditionInTraitHelper->emitError(self::class, $scope, $node->right, $rightType->getValue(), $ruleError);
                } else {
                    $errors[] = $ruleError;
                }
            } else {
                $this->emitNoError($scope, $node->right);
            }
        } else {
            $this->emitNoError($scope, $node->right);
        }
        return $errors;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    private function emitNoError(\PHPStan\Analyser\Scope $scope, Expr $expr): void
    {
        if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($expr)) {
            $this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $expr);
        } else {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $expr);
        }
    }
}
