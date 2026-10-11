<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Level;

use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\RemoveReadonlyPropertyVisibilityOnReadonlyClassRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\ClassConst\RemoveFinalFromConstRector;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Array_\RemoveDuplicatedArrayKeyRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Assign\RemoveDoubleAssignRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Assign\RemoveDoubleSelfAssignRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\BinaryOp\RemoveRedundantTypeCheckRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Block\ReplaceBlockToItsStmtsRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Cast\RecastingRemovalRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveArgumentFromDefaultParentCallRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveDuplicatedReturnSelfDocblockRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveMixedDocblockOverruledByNativeTypeRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveNullTagValueNodeRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveParentDelegatingClassMethodRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveParentDelegatingConstructorRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveReturnTagIncompatibleWithNativeTypeRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveTestsOverriddenPrivateMethodParameterRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUselessAssignFromPropertyPromotionRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUselessReturnExprInConstructRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUselessUnionReturnDocblockRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveVoidDocblockFromMagicMethodRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Closure\RemoveUnusedClosureVariableUseRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\ConstFetch\RemovePhpVersionIdCheckRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Expression\RemoveDeadStmtRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Expression\SimplifyMirrorAssignRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\For_\RemoveDeadContinueRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\For_\RemoveDeadIfForeachForRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\For_\RemoveDeadLoopRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\FuncCall\RemoveFilterVarOnExactTypeRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\FunctionLike\NarrowWideUnionReturnTypeRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\FunctionLike\RemoveDeadReturnRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\ReduceAlwaysFalseIfOrRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\RemoveDeadIfBlockRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\RemoveDeadInstanceOfRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\RemoveOverriddenAssignBeforeIfElseRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\RemoveTypedPropertyDeadInstanceOfRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\RemoveUnusedNonEmptyArrayBeforeForeachRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\SimplifyIfElseWithSameContentRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\MethodCall\RemoveNullNamedArgOnNullDefaultParamRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Plus\RemoveDeadZeroAndOneOperationRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Property\RemoveDefaultValueFromAssignedPropertyRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Property\RemoveUselessReadOnlyTagRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Stmt\RemoveConditionExactReturnRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Stmt\RemoveNextSameValueConditionRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\StmtsAwareInterface\RemoveDeadInstanceOfAssertRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Ternary\RemoveUselessTernaryRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Ternary\TernaryToBooleanOrFalseToBooleanAndRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\TryCatch\RemoveDeadCatchRector;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector;
/**
 * Key 0 = level 0
 * Key 50 = level 50
 *
 * Start at 0, go slowly higher, one level per PR, and improve your rule coverage
 *
 * From the safest rules to more changing ones.
 *
 * This list can change in time, based on community feedback,
 * what rules are safer than other. The safest rules will be always in the top.
 */
final class DeadCodeLevel
{
    /**
     * Mind that return type declarations are the safest to add,
     * followed by property, then params
     *
     * @var array<class-string<RectorInterface>>
     */
    public const array RULES = [
        // easy picks
        RemoveUnusedForeachKeyRector::class,
        RemoveDuplicatedArrayKeyRector::class,
        RecastingRemovalRector::class,
        RemoveAndTrueRector::class,
        SimplifyMirrorAssignRector::class,
        RemoveDeadContinueRector::class,
        RemoveUnusedNonEmptyArrayBeforeForeachRector::class,
        RemoveOverriddenAssignBeforeIfElseRector::class,
        RemoveNullPropertyInitializationRector::class,
        RemoveDefaultValueFromAssignedPropertyRector::class,
        RemoveUselessReturnExprInConstructRector::class,
        ReplaceBlockToItsStmtsRector::class,
        RemoveFilterVarOnExactTypeRector::class,
        RemoveFinalFromConstRector::class,
        UnwrapSprintfOneArgumentRector::class,
        SimplifyBoolIdenticalTrueRector::class,
        RemoveReadonlyPropertyVisibilityOnReadonlyClassRector::class,
        RemoveTypedPropertyDeadInstanceOfRector::class,
        RemoveDeadInstanceOfAssertRector::class,
        TernaryToBooleanOrFalseToBooleanAndRector::class,
        RemoveUselessTernaryRector::class,
        RemoveDoubleAssignRector::class,
        RemoveDoubleSelfAssignRector::class,
        RemoveUselessAssignFromPropertyPromotionRector::class,
        RemoveConcatAutocastRector::class,
        SimplifyIfElseWithSameContentRector::class,
        RemoveNextSameValueConditionRector::class,
        SimplifyUselessVariableRector::class,
        RemoveDeadZeroAndOneOperationRector::class,
        // docblock
        RemoveVoidDocblockFromMagicMethodRector::class,
        RemoveUselessParamTagRector::class,
        RemoveUselessReturnTagRector::class,
        RemoveDuplicatedReturnSelfDocblockRector::class,
        RemoveMixedDocblockOverruledByNativeTypeRector::class,
        RemoveReturnTagIncompatibleWithNativeTypeRector::class,
        RemoveUselessUnionReturnDocblockRector::class,
        RemoveUselessReadOnlyTagRector::class,
        RemoveNonExistingVarAnnotationRector::class,
        RemoveUselessVarTagRector::class,
        // prioritize safe belt on RemoveUseless*TagUpgrade that registered previously first
        RemoveNullTagValueNodeRector::class,
        RemovePhpVersionIdCheckRector::class,
        RemoveAlwaysTrueIfConditionRector::class,
        ReduceAlwaysFalseIfOrRector::class,
        RemoveRedundantTypeCheckRector::class,
        RemoveUnusedPrivateClassConstantRector::class,
        RemoveUnusedPrivatePropertyRector::class,
        RemoveUnusedClosureVariableUseRector::class,
        RemoveDuplicatedCaseInSwitchRector::class,
        RemoveDeadInstanceOfRector::class,
        RemoveDeadCatchRector::class,
        RemoveDeadTryCatchRector::class,
        RemoveDeadIfBlockRector::class,
        RemoveDeadIfForeachForRector::class,
        RemoveConditionExactReturnRector::class,
        RemoveDeadStmtRector::class,
        UnwrapFutureCompatibleIfPhpVersionRector::class,
        RemoveParentCallWithoutParentRector::class,
        RemoveParentDelegatingConstructorRector::class,
        RemoveParentDelegatingClassMethodRector::class,
        RemoveDeadConditionAboveReturnRector::class,
        RemoveDeadLoopRector::class,
        // removing methods could be risky if there is some magic loading them
        RemoveUnusedPromotedPropertyRector::class,
        RemoveUnusedPrivateMethodParameterRector::class,
        RemoveTestsOverriddenPrivateMethodParameterRector::class,
        RemoveUnusedPublicMethodParameterRector::class,
        RemoveUnusedPrivateMethodRector::class,
        RemoveUnreachableStatementRector::class,
        RemoveUnusedVariableAssignRector::class,
        // this could break framework magic autowiring in some cases
        RemoveUnusedConstructorParamRector::class,
        RemoveEmptyClassMethodRector::class,
        RemoveDeadReturnRector::class,
        RemoveArgumentFromDefaultParentCallRector::class,
        RemoveNullArgOnNullDefaultParamRector::class,
        RemoveNullNamedArgOnNullDefaultParamRector::class,
        NarrowWideUnionReturnTypeRector::class,
    ];
}
