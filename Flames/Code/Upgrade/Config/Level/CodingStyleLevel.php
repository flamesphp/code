<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Level;

use Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Assign\SplitDoubleAssignRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\ClassConst\SplitGroupedClassConstantsRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\CallUserFuncToMethodCallRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\StrictArraySearchRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\StrictInArrayRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\If_\AlternativeIfToBracketRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Property\SplitGroupedPropertiesRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Stmt\RemoveUselessAliasInUseStatementRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\String_\SimplifyQuoteEscapeRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Rules\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Flames\Code\Upgrade\Rules\Transform\Rector\FuncCall\FuncCallToConstFetchRector;
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
final class CodingStyleLevel
{
    /**
     * The rule order matters, as its used in withCodingStyleLevel() method
     * Place the safest rules first, followed by more complex ones
     *
     * @var array<class-string<RectorInterface>>
     */
    public const array RULES = [SeparateMultiUseImportsRector::class, NewlineBetweenClassLikeStmtsRector::class, NewlineAfterStatementRector::class, AlternativeIfToBracketRector::class, CompleteMissingIfElseBracketRector::class, SimplifyQuoteEscapeRector::class, StringClassNameToClassConstantRector::class, CatchExceptionNameMatchingTypeRector::class, SplitDoubleAssignRector::class, NewlineBeforeNewAssignSetRector::class, MakeInheritedMethodVisibilitySameAsParentRector::class, CallUserFuncArrayToVariadicRector::class, VersionCompareFuncCallToConstantRector::class, CallUserFuncToMethodCallRector::class, FuncGetArgsToVariadicParamRector::class, StrictArraySearchRector::class, StrictInArrayRector::class, UseClassKeywordForClassNameResolutionRector::class, SplitGroupedPropertiesRector::class, SplitGroupedClassConstantsRector::class, RemoveUselessAliasInUseStatementRector::class];
    /**
     * @var array<class-string<RectorInterface>, mixed[]>
     */
    public const array RULES_WITH_CONFIGURATION = [FuncCallToConstFetchRector::class => ['php_sapi_name' => 'PHP_SAPI', 'pi' => 'M_PI']];
}
