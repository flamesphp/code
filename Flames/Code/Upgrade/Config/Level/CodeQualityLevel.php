<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Level;

use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Assign\CombinedAssignRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\AssignOp\NewArrayItemConcatAssignToAssignRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Attribute\SortAttributeNamedArgsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanAnd\RemoveUselessIsObjectCheckRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanAnd\RepeatedAndNotEqualToNotInArrayRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanNot\NegatedAndsToPositiveOrsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanNot\ReplaceConstantBooleanNotRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanNot\ReplaceMultipleBooleanNotRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanOr\RepeatedOrEqualToInArrayRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\ConvertStaticToSelfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\InnerFunctionToPrivateMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassConstFetch\VariableConstFetchToClassConstFetchRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\ExplicitReturnNullRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Concat\DirnameDirConcatStringToDirectStringPathRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Expression\TernaryFalseExpressionToIfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Foreach_\ForeachToInArrayRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Foreach_\SimplifyForeachToCoalescingRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\ArrayMergeOfNonArraysToSimpleArrayRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\CallUserFuncWithArrowFunctionToInlineRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\InlineIsAInstanceOfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\IsAWithStringWithThirdArgumentRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\RemoveSoleValueSprintfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SetTypeToCastRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SimplifyFuncGetArgsCountRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SimplifyInArrayValuesRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\BooleanNotIdenticalToNotIdenticalRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\SimplifyArraySearchRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\SimplifyConditionsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\ArrayExplicitBoolCompareRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\SimplifyIfNotNullReturnRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\SimplifyIfNullableReturnRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\LogicalAnd\AndAssignsToSeparateLinesRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\New_\NewStaticToNewSelfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\NotEqual\CommonNotEqualRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\NullsafeMethodCall\CleanupUnneededNullsafeOperatorRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Property\FixClassCaseSensitivityVarDocblockRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\StmtsAwareInterface\MoveInnerFunctionToTopLevelRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Switch_\SingularSwitchToIfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Switch_\SwitchTrueToMatchRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Ternary\NumberCompareToMaxFuncCallRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Ternary\SimplifyTautologyTernaryRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Ternary\TernaryEmptyArrayArrayDimFetchToCoalesceRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Ternary\TernaryImplodeToImplodeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Ternary\TernaryConditionVariableAssignmentRector;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Rules\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Flames\Code\Upgrade\Rules\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Flames\Code\Upgrade\Rules\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Flames\Code\Upgrade\Rules\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Flames\Code\Upgrade\Rules\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector;
use Flames\Code\Upgrade\Rules\Php52\Rector\Property\VarToPublicPropertyRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\FuncCall\RenameFunctionRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
/**
 * Key 0 = level 0
 * Key 50 = level 50
 *
 * Start at 0, go slowly higher, one level per PR, and improve your rule coverage
 *
 * From the safest rules to more changing ones.
 *
 * This list can change in time, based on community feedback,
 * what rules are safer than others. The safest rules will be always in the top.
 */
final class CodeQualityLevel
{
    /**
     * The rule order matters, as it's used in the withCodeQualityLevel() method
     * Place the safest rules first, follow by more complex ones
     *
     * @var array<class-string<RectorInterface>>
     */
    public const array RULES = [FixClassCaseSensitivityVarDocblockRector::class, CombinedAssignRector::class, NewArrayItemConcatAssignToAssignRector::class, SimplifyEmptyArrayCheckRector::class, ReplaceMultipleBooleanNotRector::class, ReplaceConstantBooleanNotRector::class, ForeachToInArrayRector::class, RepeatedOrEqualToInArrayRector::class, RepeatedAndNotEqualToNotInArrayRector::class, MoveInnerFunctionToTopLevelRector::class, InnerFunctionToPrivateMethodRector::class, SimplifyForeachToCoalescingRector::class, SimplifyFuncGetArgsCountRector::class, SimplifyInArrayValuesRector::class, SimplifyStrposLowerRector::class, SimplifyArraySearchRector::class, SimplifyConditionsRector::class, SimplifyIfNotNullReturnRector::class, SimplifyIfReturnBoolRector::class, UnnecessaryTernaryExpressionRector::class, RemoveExtraParametersRector::class, SimplifyDeMorganBinaryRector::class, NegatedAndsToPositiveOrsRector::class, SimplifyTautologyTernaryRector::class, TernaryConditionVariableAssignmentRector::class, SingleInArrayToCompareRector::class, TernaryImplodeToImplodeRector::class, ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class, UseIdenticalOverEqualWithSameTypeRector::class, BooleanNotIdenticalToNotIdenticalRector::class, AndAssignsToSeparateLinesRector::class, CompactToVariablesRector::class, CompleteDynamicPropertiesRector::class, IsAWithStringWithThirdArgumentRector::class, StrlenZeroToIdenticalEmptyStringRector::class, ArrayExplicitBoolCompareRector::class, ObjectExplicitBoolCompareRector::class, ThrowWithPreviousExceptionRector::class, RemoveSoleValueSprintfRector::class, ExplicitReturnNullRector::class, ArrayMergeOfNonArraysToSimpleArrayRector::class, ArrayKeyExistsTernaryThenValueToCoalescingRector::class, AbsolutizeRequireAndIncludePathRector::class, ChangeArrayPushToArrayAssignRector::class, ForRepeatedCountToOwnVariableRector::class, ForeachItemsAssignToEmptyArrayToAssignRector::class, CommonNotEqualRector::class, SetTypeToCastRector::class, LogicalToBooleanRector::class, VarToPublicPropertyRector::class, IssetOnPropertyObjectToPropertyExistsRector::class, NewStaticToNewSelfRector::class, VariableConstFetchToClassConstFetchRector::class, SingularSwitchToIfRector::class, InlineIfToExplicitIfRector::class, TernaryFalseExpressionToIfRector::class, SwitchTrueToMatchRector::class, SimplifyIfNullableReturnRector::class, CallUserFuncWithArrowFunctionToInlineRector::class, FlipTypeControlToUseExclusiveTypeRector::class, InlineArrayReturnAssignRector::class, RemoveAlwaysElseRector::class, ChangeIfElseValueAssignToEarlyReturnRector::class, PreparedValueToEarlyReturnRector::class, ReturnEarlyIfVariableRector::class, InlineIsAInstanceOfRector::class, FlipNegatedTernaryInstanceofRector::class, InlineConstructorDefaultToPropertyRector::class, TernaryEmptyArrayArrayDimFetchToCoalesceRector::class, OptionalParametersAfterRequiredRector::class, SimplifyEmptyCheckOnEmptyArrayRector::class, CleanupUnneededNullsafeOperatorRector::class, LocallyCalledStaticMethodToNonStaticRector::class, NumberCompareToMaxFuncCallRector::class, RemoveUselessIsObjectCheckRector::class, ConvertStaticToSelfRector::class, SortCallLikeNamedArgsRector::class, SortAttributeNamedArgsRector::class, SafeDeclareStrictTypesRector::class, DirnameDirConcatStringToDirectStringPathRector::class];
    /**
     * @var array<class-string<RectorInterface>, mixed[]>
     */
    public const array RULES_WITH_CONFIGURATION = [RenameFunctionRector::class => [
        'split' => 'explode',
        'join' => 'implode',
        'sizeof' => 'count',
        # https://www.php.net/manual/en/aliases.php
        'chop' => 'rtrim',
        'doubleval' => 'floatval',
        'gzputs' => 'gzwrite',
        'fputs' => 'fwrite',
        'ini_alter' => 'ini_set',
        'is_double' => 'is_float',
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_writeable' => 'is_writable',
        'key_exists' => 'array_key_exists',
        'pos' => 'current',
        'strchr' => 'strstr',
        # mb
        'mbstrcut' => 'mb_strcut',
        'mbstrlen' => 'mb_strlen',
        'mbstrpos' => 'mb_strpos',
        'mbstrrpos' => 'mb_strrpos',
        'mbsubstr' => 'mb_substr',
    ]];
}
