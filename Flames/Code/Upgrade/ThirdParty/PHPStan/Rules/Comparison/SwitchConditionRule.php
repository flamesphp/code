<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\SwitchConditionNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
/**
 * @implements Rule<SwitchConditionNode>
 */
final class SwitchConditionRule implements Rule
{
    private \PHPStan\Rules\Comparison\ConstantConditionRuleHelper $constantConditionRuleHelper;
    private \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper;
    private \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper;
    private ExprPrinter $exprPrinter;
    private PhpVersion $phpVersion;
    private bool $treatPhpDocTypesAsCertain;
    public function __construct(\PHPStan\Rules\Comparison\ConstantConditionRuleHelper $constantConditionRuleHelper, \PHPStan\Rules\Comparison\PossiblyImpureTipHelper $possiblyImpureTipHelper, \PHPStan\Rules\Comparison\ConstantConditionInTraitHelper $constantConditionInTraitHelper, ExprPrinter $exprPrinter, PhpVersion $phpVersion, bool $treatPhpDocTypesAsCertain)
    {
        $this->constantConditionRuleHelper = $constantConditionRuleHelper;
        $this->possiblyImpureTipHelper = $possiblyImpureTipHelper;
        $this->constantConditionInTraitHelper = $constantConditionInTraitHelper;
        $this->exprPrinter = $exprPrinter;
        $this->phpVersion = $phpVersion;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
    }
    public function getNodeType(): string
    {
        return SwitchConditionNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $subject = $node->subject;
        $errors = [];
        $nextCaseIsDeadForType = \false;
        $nextCaseIsDeadForNativeType = \false;
        $seenCases = [];
        foreach ($node->arms as $arm) {
            if ($nextCaseIsDeadForNativeType || $nextCaseIsDeadForType && $this->treatPhpDocTypesAsCertain) {
                continue;
            }
            $armScope = $arm->scope;
            $caseCondition = $arm->caseCondition;
            $caseConditionType = $armScope->getType($caseCondition);
            $finiteTypes = $caseConditionType->getFiniteTypes();
            if (count($finiteTypes) === 1) {
                $caseValueType = $finiteTypes[0];
                $firstSeen = null;
                foreach ($seenCases as $seenCase) {
                    if ($this->isDuplicateCase($seenCase['type'], $caseValueType)) {
                        $firstSeen = $seenCase;
                        break;
                    }
                }
                if ($firstSeen !== null) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Case %s in switch is a duplicate of case %s on line %d.', $this->exprPrinter->printExpr($caseCondition), $firstSeen['printed'], $firstSeen['line']))->line($arm->line)->identifier('switch.duplicateCase')->build();
                    continue;
                }
                $seenCases[] = ['type' => $caseValueType, 'printed' => $this->exprPrinter->printExpr($caseCondition), 'line' => $arm->line];
            }
            $conditionExpr = new Equal($subject, $caseCondition);
            $conditionType = $armScope->getType($conditionExpr);
            if (!$this->isConstantBoolean($conditionType)) {
                $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
                continue;
            }
            if ($conditionType->isTrue()->yes()) {
                $nextCaseIsDeadForType = \true;
            }
            if (!$this->treatPhpDocTypesAsCertain) {
                $conditionNativeType = $armScope->getNativeType($conditionExpr);
                if (!$this->isConstantBoolean($conditionNativeType)) {
                    $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
                    continue;
                }
                if ($conditionNativeType->isTrue()->yes()) {
                    $nextCaseIsDeadForNativeType = \true;
                }
            }
            $subjectType = $armScope->getType($subject);
            if ($this->isConstantBoolean($subjectType)) {
                $caseConditionStandaloneType = $this->constantConditionRuleHelper->getBooleanType($armScope, $caseCondition);
                if (!$this->isConstantBoolean($caseConditionStandaloneType)) {
                    $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
                    continue;
                }
            }
            if ($conditionType->isFalse()->yes()) {
                $errorBuilder = RuleErrorBuilder::message(sprintf('Switch condition comparison between %s and %s is always false.', $subjectType->describe(VerbosityLevel::value()), $caseConditionType->describe(VerbosityLevel::value())))->line($arm->line)->identifier('switch.alwaysFalse');
                $this->possiblyImpureTipHelper->addTip($armScope, $conditionExpr, $errorBuilder);
                $ruleError = $errorBuilder->build();
                if ($scope->isInTrait()) {
                    $this->constantConditionInTraitHelper->emitError(self::class, $scope, $conditionExpr, \false, $ruleError);
                } else {
                    $errors[] = $ruleError;
                }
                continue;
            }
            if ($arm->isLast) {
                $this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
                continue;
            }
            $errorBuilder = RuleErrorBuilder::message(sprintf('Switch condition comparison between %s and %s is always true.', $subjectType->describe(VerbosityLevel::value()), $armScope->getType($caseCondition)->describe(VerbosityLevel::value())))->line($arm->line)->identifier('switch.alwaysTrue')->tip('Remove remaining cases below this one and this error will disappear too.');
            $this->possiblyImpureTipHelper->addTip($armScope, $conditionExpr, $errorBuilder);
            $ruleError = $errorBuilder->build();
            if ($scope->isInTrait()) {
                $this->constantConditionInTraitHelper->emitError(self::class, $scope, $conditionExpr, \true, $ruleError);
            } else {
                $errors[] = $ruleError;
            }
        }
        return $errors;
    }
    private function isConstantBoolean(Type $type): bool
    {
        return $type->isTrue()->yes() || $type->isFalse()->yes();
    }
    /**
     * A later `case` is a duplicate of an earlier one when both match the exact
     * same set of subject values. Besides identical values, `switch` compares
     * with loose `==`, so two numerically-equal constants (e.g. 1, '1' and 1.0)
     * are duplicates too - they cannot be told apart by a `switch`.
     */
    private function isDuplicateCase(Type $seenType, Type $caseValueType): bool
    {
        if ($seenType->equals($caseValueType)) {
            return \true;
        }
        return $seenType->looseCompare($caseValueType, $this->phpVersion)->isTrue()->yes();
    }
}
