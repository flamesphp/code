<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Lexer\TokenEmulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\PhpVersion;
final class PropertyTokenEmulator extends \Flames\Code\Upgrade\ThirdParty\PhpParser\Lexer\TokenEmulator\KeywordEmulator
{
    public function getPhpVersion(): PhpVersion
    {
        return PhpVersion::fromComponents(8, 4);
    }
    public function getKeywordString(): string
    {
        return '__property__';
    }
    public function getKeywordToken(): int
    {
        return \T_PROPERTY_C;
    }
}
