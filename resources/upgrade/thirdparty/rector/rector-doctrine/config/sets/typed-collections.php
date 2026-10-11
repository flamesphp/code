<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\TypedCollections\Rector\Assign\ArrayDimFetchAssignToAddCollectionCallRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Assign\ArrayOffsetSetToSetCollectionCallRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Class_\CompleteParamDocblockFromSetterToCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Class_\CompleteReturnDocblockFromToManyRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Class_\InitializeCollectionInConstructorRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Class_\RemoveNullFromInstantiatedArrayCollectionPropertyRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\CollectionGetterNativeTypeRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\CollectionParamTypeSetterToCollectionPropertyRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\CollectionSetterParamNativeTypeRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\NarrowArrayCollectionToCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\NarrowParamUnionToCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\NarrowReturnUnionToCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\RemoveNewArrayCollectionOutsideConstructorRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\RemoveNullFromNullableCollectionTypeRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\ReturnArrayToNewArrayCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\ReturnCollectionDocblockRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Empty_\EmptyOnCollectionToIsEmptyCallRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Expression\RemoveAssertNotNullOnCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Expression\RemoveCoalesceAssignOnCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\FuncCall\ArrayMapOnCollectionToArrayRector;
use Flames\Code\Upgrade\TypedCollections\Rector\FuncCall\ArrayMergeOnCollectionToArrayRector;
use Flames\Code\Upgrade\TypedCollections\Rector\FuncCall\CurrentOnCollectionToArrayRector;
use Flames\Code\Upgrade\TypedCollections\Rector\FuncCall\InArrayOnCollectionToContainsCallRector;
use Flames\Code\Upgrade\TypedCollections\Rector\If_\RemoveIfCollectionIdenticalToNullRector;
use Flames\Code\Upgrade\TypedCollections\Rector\If_\RemoveIfInstanceofCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\If_\RemoveIsArrayOnCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\If_\RemoveUselessIsEmptyAssignRector;
use Flames\Code\Upgrade\TypedCollections\Rector\MethodCall\AssertNullOnCollectionToAssertEmptyRector;
use Flames\Code\Upgrade\TypedCollections\Rector\MethodCall\AssertSameCountOnCollectionToAssertCountRector;
use Flames\Code\Upgrade\TypedCollections\Rector\MethodCall\SetArrayToNewCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\New_\RemoveNewArrayCollectionWrapRector;
use Flames\Code\Upgrade\TypedCollections\Rector\NullsafeMethodCall\RemoveNullsafeOnCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Property\NarrowPropertyUnionToCollectionRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Property\TypedPropertyFromToManyRelationTypeRector;
return static function (UpgradeConfig $rectorConfig): void {
    // rule that handle docblocks only, safer to apply
    $rectorConfig->import(__DIR__ . '/typed-collections-docblocks.php');
    $rectorConfig->rules([
        // init
        InitializeCollectionInConstructorRector::class,
        RemoveNullFromInstantiatedArrayCollectionPropertyRector::class,
        RemoveNewArrayCollectionOutsideConstructorRector::class,
        // cleanups
        RemoveCoalesceAssignOnCollectionRector::class,
        RemoveIfInstanceofCollectionRector::class,
        RemoveIsArrayOnCollectionRector::class,
        RemoveIfCollectionIdenticalToNullRector::class,
        // collection method calls
        ArrayDimFetchAssignToAddCollectionCallRector::class,
        ArrayOffsetSetToSetCollectionCallRector::class,
        ArrayMapOnCollectionToArrayRector::class,
        ArrayMergeOnCollectionToArrayRector::class,
        CurrentOnCollectionToArrayRector::class,
        EmptyOnCollectionToIsEmptyCallRector::class,
        InArrayOnCollectionToContainsCallRector::class,
        // native type declarations
        CollectionGetterNativeTypeRector::class,
        CollectionSetterParamNativeTypeRector::class,
        CollectionParamTypeSetterToCollectionPropertyRector::class,
        TypedPropertyFromToManyRelationTypeRector::class,
        RemoveNullFromNullableCollectionTypeRector::class,
        // docblocks
        NarrowArrayCollectionToCollectionRector::class,
        // @param docblock
        CompleteParamDocblockFromSetterToCollectionRector::class,
        NarrowParamUnionToCollectionRector::class,
        // @var docblock
        NarrowPropertyUnionToCollectionRector::class,
        // @return docblock
        NarrowReturnUnionToCollectionRector::class,
        CompleteReturnDocblockFromToManyRector::class,
        ReturnCollectionDocblockRector::class,
        // new ArrayCollection() wraps
        ReturnArrayToNewArrayCollectionRector::class,
        SetArrayToNewCollectionRector::class,
        RemoveNewArrayCollectionWrapRector::class,
        // cleanup
        RemoveNullsafeOnCollectionRector::class,
        RemoveUselessIsEmptyAssignRector::class,
        // test assertions
        RemoveAssertNotNullOnCollectionRector::class,
        AssertNullOnCollectionToAssertEmptyRector::class,
        AssertSameCountOnCollectionToAssertCountRector::class,
    ]);
};
