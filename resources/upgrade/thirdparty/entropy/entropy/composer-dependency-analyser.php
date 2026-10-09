<?php

// @see https://github.com/shipmonk-rnd/composer-dependency-analyser/
declare (strict_types=1);
namespace FlamesPrefix202610;

use FlamesPrefix202610\ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use FlamesPrefix202610\ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;
return new Configuration()->ignoreErrorsOnExtension('ext-filter', [ErrorType::SHADOW_DEPENDENCY])->ignoreErrorsOnPackage('symfony/polyfill-php80', [ErrorType::UNUSED_DEPENDENCY]);
