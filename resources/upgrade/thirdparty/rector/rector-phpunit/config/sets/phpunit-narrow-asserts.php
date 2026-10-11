<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Expression\AssertArrayCastedObjectToAssertSameRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertCompareOnCountableWithMethodToAssertCountRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertComparisonToSpecificMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertEmptyNullableObjectToAssertInstanceofRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertEqualsToSameRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertFalseStrposToContainsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertInstanceOfComparisonRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertIssetToSpecificMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertNotOperatorRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertSameBoolNullToSpecificMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertSameTrueFalseToAssertTrueFalseRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertTrueFalseToSpecificMethodRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\MatchAssertSameExpectedTypeRector;
/**
 * Narrow broad asserts to their specific, more descriptive method calls,
 * e.g. assertTrue(isset($a['b'])) => assertArrayHasKey('b', $a).
 */
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([AssertCompareOnCountableWithMethodToAssertCountRector::class, AssertComparisonToSpecificMethodRector::class, AssertNotOperatorRector::class, AssertTrueFalseToSpecificMethodRector::class, AssertSameBoolNullToSpecificMethodRector::class, AssertFalseStrposToContainsRector::class, AssertIssetToSpecificMethodRector::class, AssertInstanceOfComparisonRector::class, AssertEmptyNullableObjectToAssertInstanceofRector::class, AssertEqualsToSameRector::class, AssertSameTrueFalseToAssertTrueFalseRector::class, MatchAssertSameExpectedTypeRector::class, AssertArrayCastedObjectToAssertSameRector::class]);
};
