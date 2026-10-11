<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Php;

use PHPStan\Analyser\Scope;
final class ReservedKeywordAnalyzer
{
    public function isNativeVariable(string $name): bool
    {
        return in_array($name, Scope::SUPERGLOBAL_VARIABLES, \true);
    }
}
