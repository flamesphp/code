<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionCallExpressionNode;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<FunctionCallExpressionNode>
 */
#[RegisteredRule(4)]
final class ImpossibleCheckTypeFunctionCallRule implements Rule
{
    private \PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper $impossibleCheckTypeHelper;
    private \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper;
    private \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper;
    private \PHPStan\Rules\Comparison\FunctionCallConstantConditionHelper $functionCallConstantConditionHelper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $reportAlwaysTrueInLastCondition;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(
        \PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
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
        $this->impossibleCheckTypeHelper = $impossibleCheckTypeHelper;
        $this->possiblyImpureTipHelper = $possiblyImpureTipHelper;
        $this->constantConditionInTraitHelper = $constantConditionInTraitHelper;
        $this->functionCallConstantConditionHelper = $functionCallConstantConditionHelper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return FunctionCallExpressionNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $funcCall = $node->originalNode;
        $nodeResult = $node->result;
        if (!$funcCall->name instanceof Node\Name) {
            return [];
        }
        $functionName = (string) $funcCall->name;
        $reasons = [];
        $argsResult = $node->argsResult;
        $isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $funcCall, $nodeResult, $argsResult, $reasons);
        if ($isAlways === null) {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $funcCall);
            return [];
        }
        $this->functionCallConstantConditionHelper->emitImpossibleCheckReported($scope, $funcCall);
        $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $funcCall, $nodeResult, $reasons, $argsResult): RuleErrorBuilder {
            if ($reasons !== []) {
                return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder->acceptsReasonsTip($reasons));
            }
            if (!$this->treatPhpDocTypesAsCertain) {
                return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder);
            }
            $isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $funcCall, $nodeResult, $argsResult, $reasons);
            if ($isAlways !== null) {
                return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder);
            }
            if (!$this->treatPhpDocTypesAsCertainTip) {
                return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder);
            }
            $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
            return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder);
        };
        if (!$isAlways) {
            $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Call to function %s()%s will always evaluate to false.', $functionName, $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $funcCall->getArgs()))));
            $ruleError = $errorBuilder->identifier('function.impossibleType')->build();
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $funcCall, \false, $ruleError);
                return [];
            }
            return [$ruleError];
        }
        $isLast = $funcCall->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
        if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $funcCall);
            return [];
        }
        $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Call to function %s()%s will always evaluate to true.', $functionName, $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $funcCall->getArgs()))));
        if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
            $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
        }
        $errorBuilder->identifier('function.alreadyNarrowedType');
        $ruleError = $errorBuilder->build();
        if ($scope->isInTrait()) {
            $this->constantConditionInTraitHelper->emitError(self::class, $scope, $funcCall, \true, $ruleError);
            return [];
        }
        return [$ruleError];
    }
}
