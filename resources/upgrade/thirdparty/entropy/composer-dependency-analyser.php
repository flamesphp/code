<?php

// @see https://github.com/shipmonk-rnd/composer-dependency-analyser/
declare (strict_types=1);
namespace Flames\Code\Upgrade;

use Flames\Code\Upgrade\ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use Flames\Code\Upgrade\ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;
return new Configuration()->ignoreErrorsOnExtension('ext-filter', [ErrorType::SHADOW_DEPENDENCY])->ignoreErrorsOnPackage('symfony/polyfill-php80', [ErrorType::UNUSED_DEPENDENCY]);
