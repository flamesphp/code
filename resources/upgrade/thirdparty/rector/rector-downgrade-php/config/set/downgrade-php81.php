<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\DowngradePhp81\Rector\Array_\DowngradeArraySpreadRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\FuncCall\DowngradeHashAlgorithmXxHashRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\LNumber\DowngradeOctalNumberRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\MethodCall\DowngradeIsEnumRector;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\DowngradePhp81\Rector\ClassConst\DowngradeFinalizePublicClassConstantRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\ClassMethod\AddReturnTypeWillChangeAttributeRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\FuncCall\DowngradeArrayIsListRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\FuncCall\DowngradeFirstClassCallableSyntaxRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike\DowngradeNeverTypeDeclarationRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike\DowngradeNewInInitializerRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike\DowngradePureIntersectionTypeRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\Instanceof_\DowngradePhp81ResourceReturnToObjectRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\Property\DowngradeReadonlyPropertyRector;
use Flames\Code\Upgrade\DowngradePhp81\Rector\StmtsAwareInterface\DowngradeSetAccessibleReflectionPropertyRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\FuncCall\RenameFunctionRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\MethodCall\RenameMethodRector;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\MethodCallRename;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_80);
    $rectorConfig->rules([AddReturnTypeWillChangeAttributeRector::class, DowngradeFinalizePublicClassConstantRector::class, DowngradeFirstClassCallableSyntaxRector::class, DowngradeNeverTypeDeclarationRector::class, DowngradePureIntersectionTypeRector::class, DowngradeNewInInitializerRector::class, DowngradePhp81ResourceReturnToObjectRector::class, DowngradeReadonlyPropertyRector::class, DowngradeArraySpreadRector::class, DowngradeArrayIsListRector::class, DowngradeSetAccessibleReflectionPropertyRector::class, DowngradeIsEnumRector::class, DowngradeOctalNumberRector::class, DowngradeHashAlgorithmXxHashRector::class]);
    // @see https://php.watch/versions/8.1/internal-method-return-types#reflection
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [new MethodCallRename('ReflectionFunction', 'hasTentativeReturnType', 'hasReturnType'), new MethodCallRename('ReflectionFunction', 'getTentativeReturnType', 'getReturnType'), new MethodCallRename('ReflectionMethod', 'hasTentativeReturnType', 'hasReturnType'), new MethodCallRename('ReflectionMethod', 'getTentativeReturnType', 'getReturnType')]);
    $rectorConfig->ruleWithConfiguration(RenameFunctionRector::class, [
        // @see https://php.watch/versions/8.1/enums#enum-exists
        'enum_exists' => 'class_exists',
    ]);
};
