<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\RemoveReturnTypeDeclarationFromCloneRector;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\DowngradePhp80\Rector\ArrayDimFetch\DowngradeDereferenceableOperationRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Catch_\DowngradeNonCapturingCatchesRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Class_\DowngradeAttributeToAnnotationRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Class_\DowngradePropertyPromotionRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\ClassConstFetch\DowngradeClassOnObjectToGetClassRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\DowngradeAbstractPrivateMethodInTraitRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\DowngradeRecursiveDirectoryIteratorHasChildrenRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\DowngradeStaticTypeDeclarationRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\DowngradeStringReturnTypeOnToStringRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\DowngradeTrailingCommasInParamUseRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Enum_\DowngradeEnumToConstantListClassRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Expression\DowngradeMatchToSwitchRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Expression\DowngradeThrowExprRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Foreach_\DowngradeDomNodeChildNodesForeachRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeArrayFilterNullableCallbackRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeNumberFormatNoFourthArgRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeStrContainsRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeStrEndsWithRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeStrStartsWithRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeSubstrFalsyRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\FunctionLike\DowngradeMixedTypeDeclarationRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\FunctionLike\DowngradeUnionTypeDeclarationRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Instanceof_\DowngradeInstanceofStringableRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Instanceof_\DowngradePhp80ResourceReturnToObjectRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall\DowngradeNamedArgumentRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall\DowngradeReflectionClassGetConstantsFilterRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall\DowngradeReflectionGetAttributesRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall\DowngradeReflectionPropertyGetDefaultValueRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\New_\DowngradeArbitraryExpressionsSupportRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\NullsafeMethodCall\DowngradeNullsafeToTernaryOperatorRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Property\DowngradeMixedTypeTypedPropertyRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\Property\DowngradeUnionTypeTypedPropertyRector;
use Flames\Code\Upgrade\DowngradePhp80\Rector\StaticCall\DowngradePhpTokenRector;
use Flames\Code\Upgrade\DowngradePhp80\ValueObject\DowngradeAttributeToAnnotation;
use Flames\Code\Upgrade\Rules\Removing\Rector\Class_\RemoveInterfacesRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_74);
    $rectorConfig->ruleWithConfiguration(RemoveInterfacesRector::class, [
        // @see https://wiki.php.net/rfc/stringable
        'Stringable',
    ]);
    $rectorConfig->ruleWithConfiguration(DowngradeAttributeToAnnotationRector::class, [
        // Symfony
        new DowngradeAttributeToAnnotation('Symfony\Contracts\Service\Attribute\Required', 'required'),
        // Nette
        new DowngradeAttributeToAnnotation('Nette\DI\Attributes\Inject', 'inject'),
        // Jetbrains\PhpStorm\Language under nette/utils
        new DowngradeAttributeToAnnotation('Jetbrains\PhpStorm\Language', 'language'),
    ]);
    $rectorConfig->rules([DowngradeNamedArgumentRector::class, DowngradeDereferenceableOperationRector::class, DowngradeUnionTypeTypedPropertyRector::class, DowngradeUnionTypeDeclarationRector::class, DowngradeMixedTypeDeclarationRector::class, DowngradeStaticTypeDeclarationRector::class, DowngradeAbstractPrivateMethodInTraitRector::class, DowngradePropertyPromotionRector::class, DowngradeNonCapturingCatchesRector::class, DowngradeStrContainsRector::class, DowngradeMatchToSwitchRector::class, DowngradeClassOnObjectToGetClassRector::class, DowngradeArbitraryExpressionsSupportRector::class, DowngradeNullsafeToTernaryOperatorRector::class, DowngradeTrailingCommasInParamUseRector::class, DowngradeStrStartsWithRector::class, DowngradeStrEndsWithRector::class, DowngradePhpTokenRector::class, DowngradeThrowExprRector::class, DowngradeDomNodeChildNodesForeachRector::class, DowngradePhp80ResourceReturnToObjectRector::class, DowngradeReflectionGetAttributesRector::class, DowngradeRecursiveDirectoryIteratorHasChildrenRector::class, DowngradeReflectionPropertyGetDefaultValueRector::class, DowngradeReflectionClassGetConstantsFilterRector::class, DowngradeArrayFilterNullableCallbackRector::class, DowngradeNumberFormatNoFourthArgRector::class, DowngradeStringReturnTypeOnToStringRector::class, DowngradeMixedTypeTypedPropertyRector::class, RemoveReturnTypeDeclarationFromCloneRector::class, DowngradeEnumToConstantListClassRector::class, DowngradeInstanceofStringableRector::class, DowngradeSubstrFalsyRector::class]);
};
