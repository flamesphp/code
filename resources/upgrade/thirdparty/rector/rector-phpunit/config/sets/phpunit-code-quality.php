<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\CallLike\DirectInstanceOverMockArgRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddParamTypeFromDependsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddReturnTypeToDependedRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddStubIntersectionVarToStubPropertyRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\ConstructClassMethodToSetUpTestCaseRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\InlineStubPropertyToCreateStubMethodCallRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\NarrowUnusedSetUpDefinedPropertyRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\RemoveNeverUsedMockPropertyRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\SingleMockPropertyTypeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\TestWithToDataProviderRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\TypeWillReturnCallableArrowFunctionRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\VoidMethodWithCallbackToWillReturnCallbackRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\AddInstanceofAssertForNullableArgumentRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\AddInstanceofAssertForNullableInstanceRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\BareCreateMockAssignToDirectUseRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\ChangeMockObjectReturnUnionToIntersectionRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\DataProviderArrayItemsNewLinedRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\EntityDocumentCreateMockToDirectNewRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\NoSetupWithParentCallOverrideRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\RemoveEmptyTestMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\RemoveStandaloneCreateMockRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\ReplaceTestAnnotationWithPrefixedFunctionRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Expression\AssertArrayCastedObjectToAssertSameRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Foreach_\SimplifyForeachInstanceOfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\AssertFuncCallToPHPUnitAssertRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertCompareOnCountableWithMethodToAssertCountRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertComparisonToSpecificMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertEmptyNullableObjectToAssertInstanceofRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertEqualsToSameRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertFalseStrposToContainsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertInstanceOfComparisonRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertIssetToSpecificMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertNotOperatorRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertSameBoolNullToSpecificMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertSameTrueFalseToAssertTrueFalseRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertTrueFalseToSpecificMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\CallbackSingleAssertToSimplerRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\FlipAssertRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\MatchAssertSameExpectedTypeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\MergeWithCallableAndWillReturnRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\NarrowIdenticalWithConsecutiveRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\NarrowSingleWillReturnCallbackRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\RemoveExpectAnyFromMockRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\ScalarArgumentToExpectedParamTypeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\SimplerWithIsInstanceOfRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\SingleWithConsecutiveToWithRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\StringCastAssertStringContainsStringRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\UseSpecificWillMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\UseSpecificWithMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\WillReturnCallbackFallbackToReturnFalseRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\WithCallbackIdenticalToStandaloneAssertsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\StmtsAwareInterface\DeclareStrictTypesTestsRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\Class_\PropertyCreateMockToCreateStubRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\Property\MockObjectVarToStubRector;
use Flames\Code\Upgrade\PHPUnit60\Rector\MethodCall\GetMockBuilderGetMockToCreateMockRector;
use Flames\Code\Upgrade\PHPUnit90\Rector\MethodCall\ReplaceAtMethodWithDesiredMatcherRector;
use Flames\Code\Upgrade\Rules\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        ConstructClassMethodToSetUpTestCaseRector::class,
        AssertSameTrueFalseToAssertTrueFalseRector::class,
        MatchAssertSameExpectedTypeRector::class,
        AssertEqualsToSameRector::class,
        PreferPHPUnitThisCallRector::class,
        YieldDataProviderRector::class,
        RemoveEmptyTestMethodRector::class,
        ReplaceTestAnnotationWithPrefixedFunctionRector::class,
        TestWithToDataProviderRector::class,
        AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector::class,
        DataProviderArrayItemsNewLinedRector::class,
        FlipAssertRector::class,
        // narrow with consecutive
        NarrowIdenticalWithConsecutiveRector::class,
        NarrowSingleWillReturnCallbackRector::class,
        WillReturnCallbackFallbackToReturnFalseRector::class,
        SingleWithConsecutiveToWithRector::class,
        // type declarations
        TypeWillReturnCallableArrowFunctionRector::class,
        VoidMethodWithCallbackToWillReturnCallbackRector::class,
        StringCastAssertStringContainsStringRector::class,
        AddParamTypeFromDependsRector::class,
        AddReturnTypeToDependedRector::class,
        ScalarArgumentToExpectedParamTypeRector::class,
        NarrowUnusedSetUpDefinedPropertyRector::class,
        // specific asserts
        AssertCompareOnCountableWithMethodToAssertCountRector::class,
        AssertComparisonToSpecificMethodRector::class,
        AssertNotOperatorRector::class,
        AssertTrueFalseToSpecificMethodRector::class,
        AssertSameBoolNullToSpecificMethodRector::class,
        AssertFalseStrposToContainsRector::class,
        AssertIssetToSpecificMethodRector::class,
        AssertInstanceOfComparisonRector::class,
        AssertFuncCallToPHPUnitAssertRector::class,
        SimplifyForeachInstanceOfRector::class,
        UseSpecificWillMethodRector::class,
        UseSpecificWithMethodRector::class,
        AssertEmptyNullableObjectToAssertInstanceofRector::class,
        // avoid call on nullable object
        AddInstanceofAssertForNullableInstanceRector::class,
        AddInstanceofAssertForNullableArgumentRector::class,
        // @todo test first
        // \Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\AddKeysExistsAssertForKeyUseRector::class,
        AssertArrayCastedObjectToAssertSameRector::class,
        /**
         * Improve direct testing of your code, without mock creep. Make it simple, clear and easy to maintain:
         *
         * @see https://blog.frankdejonge.nl/testing-without-mocking-frameworks/
         * @see https://maksimivanov.com/posts/dont-mock-what-you-dont-own/
         * @see https://dev.to/mguinea/stop-using-mocking-libraries-2f2k
         * @see https://mnapoli.fr/anonymous-classes-in-tests/
         * @see https://steemit.com/php/@crell/don-t-use-mocking-libraries
         * @see https://davegebler.com/post/php/better-php-unit-testing-avoiding-mocks
         */
        RemoveExpectAnyFromMockRector::class,
        SingleMockPropertyTypeRector::class,
        CallbackSingleAssertToSimplerRector::class,
        SimplerWithIsInstanceOfRector::class,
        DirectInstanceOverMockArgRector::class,
        // stub over mock
        CreateStubOverCreateMockArgRector::class,
        ExpressionCreateMockToCreateStubRector::class,
        PropertyCreateMockToCreateStubRector::class,
        MockObjectVarToStubRector::class,
        AddStubIntersectionVarToStubPropertyRector::class,
        InlineStubPropertyToCreateStubMethodCallRector::class,
        FinalizeTestCaseClassRector::class,
        DeclareStrictTypesTestsRector::class,
        WithCallbackIdenticalToStandaloneAssertsRector::class,
        MergeWithCallableAndWillReturnRector::class,
        // prefer simple mocking
        GetMockBuilderGetMockToCreateMockRector::class,
        EntityDocumentCreateMockToDirectNewRector::class,
        ReplaceAtMethodWithDesiredMatcherRector::class,
        BareCreateMockAssignToDirectUseRector::class,
        ChangeMockObjectReturnUnionToIntersectionRector::class,
        DecorateWillReturnMapWithExpectsMockRector::class,
        // dead code
        RemoveNeverUsedMockPropertyRector::class,
        RemoveStandaloneCreateMockRector::class,
        // readability
        NoSetupWithParentCallOverrideRector::class,
    ]);
};
