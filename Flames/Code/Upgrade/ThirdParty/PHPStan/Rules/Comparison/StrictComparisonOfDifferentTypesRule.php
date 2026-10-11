<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
#[RegisteredRule(4)]
final class StrictComparisonOfDifferentTypesRule implements Rule
{
    private RicherScopeGetTypeHelper $richerScopeGetTypeHelper;
    private \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper;
    private \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $reportAlwaysTrueInLastCondition;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(
        RicherScopeGetTypeHelper $richerScopeGetTypeHelper,
        \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper,
        \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper,
        #[AutowiredParameter]
        bool $treatPhpDocTypesAsCertain,
        #[AutowiredParameter]
        bool $reportAlwaysTrueInLastCondition,
        #[AutowiredParameter('%tips.treatPhpDocTypesAsCertain%')]
        bool $treatPhpDocTypesAsCertainTip
    )
    {
        $this->richerScopeGetTypeHelper = $richerScopeGetTypeHelper;
        $this->possiblyImpureTipHelper = $possiblyImpureTipHelper;
        $this->constantConditionInTraitHelper = $constantConditionInTraitHelper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return Node\Expr\BinaryOp::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        if (!$scope instanceof MutatingScope) {
            throw new ShouldNotHappenException();
        }
        if ($node instanceof Node\Expr\BinaryOp\Identical) {
            $nodeTypeResult = $this->richerScopeGetTypeHelper->getIdenticalResult($this->treatPhpDocTypesAsCertain ? $scope : $scope->doNotTreatPhpDocTypesAsCertain(), $node);
        } elseif ($node instanceof Node\Expr\BinaryOp\NotIdentical) {
            $nodeTypeResult = $this->richerScopeGetTypeHelper->getNotIdenticalResult($this->treatPhpDocTypesAsCertain ? $scope : $scope->doNotTreatPhpDocTypesAsCertain(), $node);
        } else {
            return [];
        }
        $nodeType = $nodeTypeResult->type;
        if (!$nodeType instanceof ConstantBooleanType) {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
            return [];
        }
        $leftType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->left) : $scope->getNativeType($node->left);
        $rightType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->right) : $scope->getNativeType($node->right);
        $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node, $nodeTypeResult): RuleErrorBuilder {
            $reasons = $nodeTypeResult->reasons;
            if (count($reasons) > 0) {
                $ruleErrorBuilder = $ruleErrorBuilder->acceptsReasonsTip($reasons);
                return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
            }
            if (!$this->treatPhpDocTypesAsCertain) {
                return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
            }
            $instanceofTypeWithoutPhpDocs = $scope->getNativeType($node);
            if ($instanceofTypeWithoutPhpDocs instanceof ConstantBooleanType) {
                return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
            }
            if (!$this->treatPhpDocTypesAsCertainTip) {
                return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
            }
            $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
            return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
        };
        $verbosity = VerbosityLevel::value();
        if ($leftType->isConstantScalarValue()->yes() && !$leftType->isString()->no() && !$rightType->isConstantScalarValue()->yes() && !$rightType->isString()->no() && (TrinaryLogic::extremeIdentity($leftType->isLowercaseString(), $rightType->isLowercaseString())->maybe() || TrinaryLogic::extremeIdentity($leftType->isUppercaseString(), $rightType->isUppercaseString())->maybe()) || $rightType->isConstantScalarValue()->yes() && !$rightType->isString()->no() && !$leftType->isConstantScalarValue()->yes() && !$leftType->isString()->no() && (TrinaryLogic::extremeIdentity($leftType->isLowercaseString(), $rightType->isLowercaseString())->maybe() || TrinaryLogic::extremeIdentity($leftType->isUppercaseString(), $rightType->isUppercaseString())->maybe())) {
            $verbosity = VerbosityLevel::precise();
        }
        if (!$nodeType->getValue()) {
            $ruleError = $addTip(RuleErrorBuilder::message(sprintf('Strict comparison using %s between %s and %s will always evaluate to false.', $node->getOperatorSigil(), $leftType->describe($verbosity), $rightType->describe($verbosity))))->identifier(sprintf('%s.alwaysFalse', $node instanceof Node\Expr\BinaryOp\Identical ? 'identical' : 'notIdentical'))->build();
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, \false, $ruleError);
                return [];
            }
            return [$ruleError];
        }
        $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
        if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
                return [];
            }
            return [];
        }
        $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Strict comparison using %s between %s and %s will always evaluate to true.', $node->getOperatorSigil(), $leftType->describe($verbosity), $rightType->describe($verbosity))));
        if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
            $errorBuilder->addTip('Remove remaining cases below this one and this error will disappear too.');
        }
        if ($leftType->isEnum()->yes() && $rightType->isEnum()->yes() && $node->getAttribute(LastConditionVisitor::ATTRIBUTE_IS_MATCH_NAME, \false) !== \true) {
            $errorBuilder->addTip('Use match expression instead. PHPStan will report unhandled enum cases.');
        }
        $errorBuilder->identifier(sprintf('%s.alwaysTrue', $node instanceof Node\Expr\BinaryOp\Identical ? 'identical' : 'notIdentical'));
        $ruleError = $errorBuilder->build();
        if ($scope->isInTrait()) {
            $this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, \true, $ruleError);
            return [];
        }
        return [$ruleError];
    }
}
