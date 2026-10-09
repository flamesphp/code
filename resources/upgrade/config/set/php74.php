<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use PhpParser\Node\Expr\Cast\Double;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php74\Rector\ArrayDimFetch\CurlyToSquareBracketArrayStringRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\Assign\NullCoalescingOperatorRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\ArrayKeyExistsOnPropertyRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\FilterVarToAddSlashesRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\HebrevcToNl2brHebrevRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\MbStrrposEncodingArgumentPositionRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\MoneyFormatToNumberFormatRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\RestoreIncludePathToIniRestoreRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\If_\IfToNullCoalescingAssignRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\StaticCall\ExportToReflectionFunctionRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\Ternary\ParenthesizeNestedTernaryRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\Cast\RenameCastRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\FuncCall\RenameFunctionRector;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameCast;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameFunctionRector::class, [
        # the_real_type
        # https://wiki.php.net/rfc/deprecations_php_7_4
        'is_real' => 'is_float',
    ]);
    $rectorConfig->rules([ArrayKeyExistsOnPropertyRector::class, FilterVarToAddSlashesRector::class, ExportToReflectionFunctionRector::class, MbStrrposEncodingArgumentPositionRector::class, NullCoalescingOperatorRector::class, IfToNullCoalescingAssignRector::class, ClosureToArrowFunctionRector::class, RestoreDefaultNullToNullableTypePropertyRector::class, CurlyToSquareBracketArrayStringRector::class, MoneyFormatToNumberFormatRector::class, ParenthesizeNestedTernaryRector::class, RestoreIncludePathToIniRestoreRector::class, HebrevcToNl2brHebrevRector::class]);
    $rectorConfig->ruleWithConfiguration(RenameCastRector::class, [new RenameCast(Double::class, Double::KIND_REAL, Double::KIND_FLOAT)]);
};
