<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\BreaklessWhileLoopNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
/**
 * @implements Rule<BreaklessWhileLoopNode>
 */
#[RegisteredRule(4)]
final class WhileLoopAlwaysTrueConditionRule implements Rule
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
        return BreaklessWhileLoopNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        foreach ($node->exitPoints as $exitPoint) {
            $statement = $exitPoint->getStatement();
            if ($statement instanceof Break_) {
                return [];
            }
            if (!$statement instanceof Continue_) {
                return [];
            }
            if ($statement->num === null) {
                continue;
            }
            if (!$statement->num instanceof Int_) {
                continue;
            }
            $value = $statement->num->value;
            if ($value === 1) {
                continue;
            }
            if ($value > 1) {
                return [];
            }
        }
        $originalNode = $node->originalNode;
        $exprType = $this->helper->getBooleanType($scope, $originalNode->cond);
        $isTypeCheckCandidate = $this->functionCallConstantConditionHelper->isTypeCheckCandidate($originalNode->cond);
        if ($exprType->isTrue()->yes()) {
            if ($node->hasYield) {
                if ($isTypeCheckCandidate) {
                    $this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $originalNode->cond);
                } else {
                    $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $originalNode->cond);
                }
                return [];
            }
            $ref = $scope->getFunction() ?? $scope->getAnonymousFunctionReflection();
            if ($ref !== null && $ref->getReturnType() instanceof NeverType) {
                if ($isTypeCheckCandidate) {
                    $this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $originalNode->cond);
                } else {
                    $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $originalNode->cond);
                }
                return [];
            }
            $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $originalNode): RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $originalNode->cond, $ruleErrorBuilder);
                }
                $booleanNativeType = $this->helper->getNativeBooleanType($scope, $originalNode->cond);
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $originalNode->cond, $ruleErrorBuilder);
                }
                if (!$this->treatPhpDocTypesAsCertainTip) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $originalNode->cond, $ruleErrorBuilder);
                }
                $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
                return $this->possiblyImpureTipHelper->addTip($scope, $originalNode->cond, $ruleErrorBuilder);
            };
            $ruleError = $addTip(RuleErrorBuilder::message('While loop condition is always true.'))->line($originalNode->cond->getStartLine())->identifier('while.alwaysTrue')->build();
            if ($isTypeCheckCandidate) {
                $this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $originalNode->cond, \true, $ruleError);
                return [];
            }
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $originalNode->cond, \true, $ruleError);
                return [];
            }
            return [$ruleError];
        }
        if ($isTypeCheckCandidate) {
            $this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $originalNode->cond);
        } else {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $originalNode->cond);
        }
        return [];
    }
}
