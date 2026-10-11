<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
#[RegisteredRule(4)]
final class ConstantLooseComparisonRule implements Rule
{
    private \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper;
    private \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $reportAlwaysTrueInLastCondition;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(
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
        if (!$node instanceof Node\Expr\BinaryOp\Equal && !$node instanceof Node\Expr\BinaryOp\NotEqual) {
            return [];
        }
        $nodeType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
        if (!$nodeType->isTrue()->yes() && !$nodeType->isFalse()->yes()) {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
            return [];
        }
        $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
            if (!$this->treatPhpDocTypesAsCertain) {
                return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
            }
            $instanceofTypeWithoutPhpDocs = $scope->getNativeType($node);
            if ($instanceofTypeWithoutPhpDocs->isTrue()->yes() || $instanceofTypeWithoutPhpDocs->isFalse()->yes()) {
                return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
            }
            if (!$this->treatPhpDocTypesAsCertainTip) {
                return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
            }
            $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
            return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
        };
        if ($nodeType->isFalse()->yes()) {
            $ruleError = $addTip(RuleErrorBuilder::message(sprintf('Loose comparison using %s between %s and %s will always evaluate to false.', $node->getOperatorSigil(), $scope->getType($node->left)->describe(VerbosityLevel::value()), $scope->getType($node->right)->describe(VerbosityLevel::value()))))->identifier(sprintf('%s.alwaysFalse', $node instanceof Node\Expr\BinaryOp\Equal ? 'equal' : 'notEqual'))->build();
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, \false, $ruleError);
                return [];
            }
            return [$ruleError];
        }
        $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
        if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
            $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
            return [];
        }
        $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Loose comparison using %s between %s and %s will always evaluate to true.', $node->getOperatorSigil(), $scope->getType($node->left)->describe(VerbosityLevel::value()), $scope->getType($node->right)->describe(VerbosityLevel::value()))));
        if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
            $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
        }
        $errorBuilder->identifier(sprintf('%s.alwaysTrue', $node instanceof Node\Expr\BinaryOp\Equal ? 'equal' : 'notEqual'));
        $ruleError = $errorBuilder->build();
        if ($scope->isInTrait()) {
            $this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, \true, $ruleError);
            return [];
        }
        return [$ruleError];
    }
}
