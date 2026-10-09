<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Level;

use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamArrayDocblockBasedOnCallableNativeFuncCallRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddReturnDocblockForScalarArrayFromAssignsRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\NarrowBoolDocblockReturnTypeRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\AddVarArrayDocblockFromDimFetchAssignRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\ClassMethodArrayDocblockParamFromLocalCallsRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\DocblockVarArrayFromGetterReturnRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\DocblockVarArrayFromPropertyDefaultsRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\DocblockVarFromParamDocblockInConstructorRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromAssignsParamToParamReferenceRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromDimFetchAccessRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockForArrayDimAssignedObjectRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockForCommonObjectDenominatorRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockForJsonArrayRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\DocblockGetterReturnArrayFromPropertyDocblockVarRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\DocblockReturnArrayFromDirectArrayInstanceRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\NarrowArrayCollectionUnionReturnDocblockRector;
final class TypeDeclarationDocblocksLevel
{
    /**
     * @var array<class-string<RectorInterface>>
     */
    public const array RULES = [
        // start with rules based on native code
        // property var
        DocblockVarArrayFromPropertyDefaultsRector::class,
        // param
        AddParamArrayDocblockFromDimFetchAccessRector::class,
        ClassMethodArrayDocblockParamFromLocalCallsRector::class,
        AddParamArrayDocblockFromAssignsParamToParamReferenceRector::class,
        AddParamArrayDocblockBasedOnCallableNativeFuncCallRector::class,
        // return
        AddReturnDocblockForCommonObjectDenominatorRector::class,
        AddReturnDocblockForScalarArrayFromAssignsRector::class,
        DocblockReturnArrayFromDirectArrayInstanceRector::class,
        AddReturnDocblockForArrayDimAssignedObjectRector::class,
        AddReturnDocblockForJsonArrayRector::class,
        // move to rules based on existing docblocks, as more risky
        // property var
        DocblockVarFromParamDocblockInConstructorRector::class,
        DocblockVarArrayFromGetterReturnRector::class,
        AddVarArrayDocblockFromDimFetchAssignRector::class,
        // return
        DocblockGetterReturnArrayFromPropertyDocblockVarRector::class,
        NarrowArrayCollectionUnionReturnDocblockRector::class,
        NarrowBoolDocblockReturnTypeRector::class,
    ];
}
