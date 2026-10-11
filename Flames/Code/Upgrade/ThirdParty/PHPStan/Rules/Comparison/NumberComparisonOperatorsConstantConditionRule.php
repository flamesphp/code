<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function get_class;
use function sprintf;
/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
#[RegisteredRule(4)]
final class NumberComparisonOperatorsConstantConditionRule implements Rule
{
    private \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper;
    private \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(
        \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper,
        \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper,
        #[AutowiredParameter]
        bool $treatPhpDocTypesAsCertain,
        #[AutowiredParameter('%tips.treatPhpDocTypesAsCertain%')]
        bool $treatPhpDocTypesAsCertainTip
    )
    {
        $this->possiblyImpureTipHelper = $possiblyImpureTipHelper;
        $this->constantConditionInTraitHelper = $constantConditionInTraitHelper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return BinaryOp::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        if (!$node instanceof BinaryOp\Greater && !$node instanceof BinaryOp\GreaterOrEqual && !$node instanceof BinaryOp\Smaller && !$node instanceof BinaryOp\SmallerOrEqual) {
            return [];
        }
        $exprType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
        if ($exprType instanceof ConstantBooleanType) {
            $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
                }
                $booleanNativeType = $scope->getNativeType($node);
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
                }
                if (!$this->treatPhpDocTypesAsCertainTip) {
                    return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
                }
                $ruleErrorBuilder = $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
                return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
            };
            switch (get_class($node)) {
                case BinaryOp\Greater::class:
                    $nodeType = 'greater';
                    break;
                case BinaryOp\GreaterOrEqual::class:
                    $nodeType = 'greaterOrEqual';
                    break;
                case BinaryOp\Smaller::class:
                    $nodeType = 'smaller';
                    break;
                case BinaryOp\SmallerOrEqual::class:
                    $nodeType = 'smallerOrEqual';
                    break;
                default:
                    throw new ShouldNotHappenException();
            }
            $ruleError = $addTip(RuleErrorBuilder::message(sprintf('Comparison operation "%s" between %s and %s is always %s.', $node->getOperatorSigil(), $scope->getType($node->left)->describe(VerbosityLevel::value()), $scope->getType($node->right)->describe(VerbosityLevel::value()), $exprType->getValue() ? 'true' : 'false')))->identifier(sprintf('%s.always%s', $nodeType, $exprType->getValue() ? 'True' : 'False'))->build();
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, $exprType->getValue(), $ruleError);
                return [];
            }
            return [$ruleError];
        }
        $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
        return [];
    }
}
