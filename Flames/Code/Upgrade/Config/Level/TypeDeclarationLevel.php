<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Level;

use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\ReturnIteratorInDataProviderRector;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ResponseReturnTypeControllerActionRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\BooleanAnd\BinaryOpNullableToInstanceofRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\AddTestsVoidReturnTypeWhereNoReturnRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\ChildDoctrineRepositoryClassTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\MergeDateTimePropertyTypeDeclarationRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\ObjectTypedPropertyFromJMSSerializerAttributeTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\PropertyTypeFromStrictSetterGetterRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\ReturnTypeFromStrictTernaryRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\ScalarTypedPropertyFromJMSSerializerAttributeTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedPropertyFromContainerGetSetUpRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedPropertyFromDocblockSetUpDefinedRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedPropertyFromGetRepositorySetUpRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedStaticPropertyInBehatContextRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamFromDimFetchKeyUseRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamStringTypeFromSprintfUseRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamTypeBasedOnPHPUnitDataProviderRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddReturnTypeFromTryCatchTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ArrayParamTypeByMethodCallTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanConstAndStrictReturnsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanConstReturnsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanStrictReturnsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\KnownMagicClassMethodTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\NarrowObjectReturnTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\NumericReturnTypeFromStrictReturnsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\NumericReturnTypeFromStrictScalarReturnsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ObjectParamTypeByMethodCallTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\PrivateMethodReturnTypeFromStrictNewArrayRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnNullableTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromGetRepositoryDocblockRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromMockObjectRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnCastRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictConstantReturnRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictParamRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromSymfonySerializerRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ScalarParamTypeByMethodCallTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Closure\AddClosureNeverReturnTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Closure\ClosureReturnTypeFromAssertInstanceOfRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Empty_\EmptyOnNullableObjectToInstanceOfRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FuncCall\AddArrayAnyAllClosureParamTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FuncCall\AddArrayFunctionClosureParamTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FuncCall\AddArrowFunctionParamArrayWhereDimFetchRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FuncCall\NarrowArrayAnyAllNullableParamTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayMapRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayReduceRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromIterableMethodCallRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromVariableCallRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike\AddParamTypeSplFixedArrayRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike\AddReturnTypeDeclarationFromYieldsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\While_\WhileNullableToInstanceofRector;
final class TypeDeclarationLevel
{
    /**
     * The rule order matters, as its used in withTypeCoverageLevel() method
     * Place the safest rules first, follow by more complex ones
     *
     * @var array<class-string<RectorInterface>>
     */
    public const array RULES = [
        // php 7.1, start with closure first, as safest
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
        AddTestsVoidReturnTypeWhereNoReturnRector::class,
        ReturnIteratorInDataProviderRector::class,
        ReturnTypeFromMockObjectRector::class,
        TypedPropertyFromCreateMockAssignRector::class,
        AddArrowFunctionReturnTypeRector::class,
        BoolReturnTypeFromBooleanConstReturnsRector::class,
        // private methods first, as safest - no external contract
        PrivateMethodReturnTypeFromStrictNewArrayRector::class,
        ReturnTypeFromStrictNewArrayRector::class,
        // scalar and array from constant
        ReturnTypeFromStrictConstantReturnRector::class,
        StringReturnTypeFromStrictScalarReturnsRector::class,
        NumericReturnTypeFromStrictScalarReturnsRector::class,
        BoolReturnTypeFromBooleanStrictReturnsRector::class,
        BoolReturnTypeFromBooleanConstAndStrictReturnsRector::class,
        StringReturnTypeFromStrictStringReturnsRector::class,
        NumericReturnTypeFromStrictReturnsRector::class,
        ReturnTypeFromStrictTernaryRector::class,
        ReturnTypeFromReturnDirectArrayRector::class,
        ResponseReturnTypeControllerActionRector::class,
        ReturnTypeFromReturnNewRector::class,
        ReturnTypeFromReturnCastRector::class,
        ReturnTypeFromSymfonySerializerRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        ReturnTypeFromStrictTypedPropertyRector::class,
        ReturnNullableTypeRector::class,
        // php 7.4
        EmptyOnNullableObjectToInstanceOfRector::class,
        WhileNullableToInstanceofRector::class,
        BinaryOpNullableToInstanceofRector::class,
        // php 7.4
        TypedPropertyFromStrictConstructorRector::class,
        AddParamTypeSplFixedArrayRector::class,
        AddReturnTypeDeclarationFromYieldsRector::class,
        AddParamTypeBasedOnPHPUnitDataProviderRector::class,
        TypedPropertyFromStrictSetUpRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        AddReturnTypeFromTryCatchTypeRector::class,
        ReturnTypeFromStrictTypedCallRector::class,
        ChildDoctrineRepositoryClassTypeRector::class,
        // php native types
        KnownMagicClassMethodTypeRector::class,
        // param
        AddMethodCallBasedStrictParamTypeRector::class,
        ParamTypeByParentCallTypeRector::class,
        NarrowObjectReturnTypeRector::class,
        // multi types (nullable, union)
        ReturnUnionTypeRector::class,
        // closures
        AddClosureNeverReturnTypeRector::class,
        AddClosureParamTypeForArrayMapRector::class,
        AddClosureParamTypeForArrayReduceRector::class,
        ClosureReturnTypeRector::class,
        AddArrowFunctionParamArrayWhereDimFetchRector::class,
        // more risky rules
        ReturnTypeFromStrictParamRector::class,
        AddParamTypeFromPropertyTypeRector::class,
        MergeDateTimePropertyTypeDeclarationRector::class,
        PropertyTypeFromStrictSetterGetterRector::class,
        ParamTypeByMethodCallTypeRector::class,
        ObjectParamTypeByMethodCallTypeRector::class,
        ScalarParamTypeByMethodCallTypeRector::class,
        ArrayParamTypeByMethodCallTypeRector::class,
        TypedPropertyFromContainerGetSetUpRector::class,
        TypedPropertyFromGetRepositorySetUpRector::class,
        TypedPropertyFromAssignsRector::class,
        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        ReturnTypeFromStrictFluentReturnRector::class,
        ReturnNeverTypeRector::class,
        // jms attributes
        ObjectTypedPropertyFromJMSSerializerAttributeTypeRector::class,
        ScalarTypedPropertyFromJMSSerializerAttributeTypeRector::class,
        // array parameter from dim fetch assign inside
        AddClosureParamTypeFromVariableCallRector::class,
        StrictArrayParamDimFetchRector::class,
        AddParamFromDimFetchKeyUseRector::class,
        AddParamStringTypeFromSprintfUseRector::class,
        // possibly based on docblocks, but also helpful, intentionally last
        AddArrayFunctionClosureParamTypeRector::class,
        TypedPropertyFromDocblockSetUpDefinedRector::class,
        AddClosureParamTypeFromIterableMethodCallRector::class,
        TypedStaticPropertyInBehatContextRector::class,
        // PHP 8.4
        NarrowArrayAnyAllNullableParamTypeRector::class,
        AddArrayAnyAllClosureParamTypeRector::class,
        ClosureReturnTypeFromAssertInstanceOfRector::class,
        // docblock @return into native return type, intentionally last
        ReturnTypeFromGetRepositoryDocblockRector::class,
    ];
}
