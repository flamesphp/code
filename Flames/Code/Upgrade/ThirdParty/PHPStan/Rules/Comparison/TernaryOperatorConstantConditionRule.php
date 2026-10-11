<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;
/**
 * @implements Rule<Node\Expr\Ternary>
 */
#[RegisteredRule(4)]
final class TernaryOperatorConstantConditionRule implements Rule
{
    private \PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper;
    private \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper;
    private \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper;
    private \PHPStan\Rules\Comparison\FunctionCallConstantConditionHelper $functionCallConstantConditionHelper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(
        \PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper,
        \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper,
        \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper,
        \PHPStan\Rules\Comparison\FunctionCallConstantConditionHelper $functionCallConstantConditionHelper,
        #[AutowiredParameter]
        bool $treatPhpDocTypesAsCertain,
        #[AutowiredParameter('%tips.treatPhpDocTypesAsCertain%')]
        bool $treatPhpDocTypesAsCertainTip
    )
    {
        $this->helper = $helper;
        $this->possiblyImpureTipHelper = $possiblyImpureTipHelper;
        $this->constantConditionInTraitHelper = $constantConditionInTraitHelper;
        $this->functionCallConstantConditionHelper = $functionCallConstantConditionHelper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return Node\Expr\Ternary::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $exprType = $this->helper->getBooleanType($scope, $node->cond);
        if ($exprType instanceof ConstantBooleanType) {
            $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->cond, $ruleErrorBuilder);
                }
                $booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->cond);
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->cond, $ruleErrorBuilder);
                }
                if (!$this->treatPhpDocTypesAsCertainTip) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node->cond, $ruleErrorBuilder);
                }
                $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
                return $this->possiblyImpureTipHelper->addTip($scope, $node->cond, $ruleErrorBuilder);
            };
            $ruleError = $addTip(RuleErrorBuilder::message(sprintf('Ternary operator condition is always %s.', $exprType->getValue() ? 'true' : 'false')))->identifier(sprintf('ternary.always%s', $exprType->getValue() ? 'True' : 'False'))->build();
            if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->cond)) {
                $this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $node->cond, $exprType->getValue(), $ruleError);
                return [];
            }
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $node->cond, $exprType->getValue(), $ruleError);
                return [];
            }
            return [$ruleError];
        }
        if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->cond)) {
            $this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $node->cond);
        } else {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->cond);
        }
        return [];
    }
}
