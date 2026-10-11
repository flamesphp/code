<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\StaticMethodCallExpressionNode;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;
/**
 * @implements Rule<StaticMethodCallExpressionNode>
 */
#[RegisteredRule(4)]
final class ImpossibleCheckTypeStaticMethodCallRule implements Rule
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
        return StaticMethodCallExpressionNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $staticCall = $node->originalNode;
        $nodeResult = $node->result;
        if (!$staticCall->name instanceof Node\Identifier) {
            return [];
        }
        $methodName = $staticCall->name->name;
        $reasons = [];
        $argsResult = $node->argsResult;
        $isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $staticCall, $nodeResult, $argsResult, $reasons);
        if ($isAlways === null) {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $staticCall);
            return [];
        }
        $this->functionCallConstantConditionHelper->emitImpossibleCheckReported($scope, $staticCall);
        $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $staticCall, $nodeResult, $reasons, $argsResult): RuleErrorBuilder {
            if ($reasons !== []) {
                return $this->possiblyImpureTipHelper->addTip($scope, $staticCall, $ruleErrorBuilder->acceptsReasonsTip($reasons));
            }
            if (!$this->treatPhpDocTypesAsCertain) {
                return $this->possiblyImpureTipHelper->addTip($scope, $staticCall, $ruleErrorBuilder);
            }
            $isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $staticCall, $nodeResult, $argsResult);
            if ($isAlways !== null) {
                return $this->possiblyImpureTipHelper->addTip($scope, $staticCall, $ruleErrorBuilder);
            }
            if (!$this->treatPhpDocTypesAsCertainTip) {
                return $this->possiblyImpureTipHelper->addTip($scope, $staticCall, $ruleErrorBuilder);
            }
            $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
            return $this->possiblyImpureTipHelper->addTip($scope, $staticCall, $ruleErrorBuilder);
        };
        if (!$isAlways) {
            $method = $this->getMethod($staticCall->class, $methodName, $scope);
            $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Call to static method %s::%s()%s will always evaluate to false.', $method->getDeclaringClass()->getDisplayName(), $method->getName(), $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $staticCall->getArgs()))));
            $ruleError = $errorBuilder->identifier('staticMethod.impossibleType')->build();
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $staticCall, \false, $ruleError);
                return [];
            }
            return [$ruleError];
        }
        $isLast = $staticCall->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
        if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $staticCall);
            return [];
        }
        $method = $this->getMethod($staticCall->class, $methodName, $scope);
        $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Call to static method %s::%s()%s will always evaluate to true.', $method->getDeclaringClass()->getDisplayName(), $method->getName(), $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $staticCall->getArgs()))));
        if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
            $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
        }
        $errorBuilder->identifier('staticMethod.alreadyNarrowedType');
        $ruleError = $errorBuilder->build();
        if ($scope->isInTrait()) {
            $this->constantConditionInTraitHelper->emitError(self::class, $scope, $staticCall, \true, $ruleError);
            return [];
        }
        return [$ruleError];
    }
    /**
     * @param Node\Name|Expr $class
     * @throws ShouldNotHappenException
     */
    private function getMethod($class, string $methodName, Scope $scope): MethodReflection
    {
        if ($class instanceof Node\Name) {
            $calledOnType = $scope->resolveTypeByName($class);
        } else {
            $calledOnType = $scope->getType($class);
        }
        $method = $scope->getMethodReflection($calledOnType, $methodName);
        if ($method === null) {
            throw new ShouldNotHappenException();
        }
        return $method;
    }
}
