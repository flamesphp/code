<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Parser\Php7;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Parser\Php8;
class ParserFactory
{
    /**
     * Create a parser targeting the given version on a best-effort basis. The parser will generally
     * accept code for the newest supported version, but will try to accommodate code that becomes
     * invalid in newer versions or changes in interpretation.
     */
    public function createForVersion(\Flames\Code\Upgrade\ThirdParty\PhpParser\PhpVersion $version): \Flames\Code\Upgrade\ThirdParty\PhpParser\Parser
    {
        if ($version->isHostVersion()) {
            $lexer = new \Flames\Code\Upgrade\ThirdParty\PhpParser\Lexer();
        } else {
            $lexer = new \Flames\Code\Upgrade\ThirdParty\PhpParser\Lexer\Emulative($version);
        }
        if ($version->id >= 80000) {
            return new Php8($lexer, $version);
        }
        return new Php7($lexer, $version);
    }
    /**
     * Create a parser targeting the newest version supported by this library. Code for older
     * versions will be accepted if there have been no relevant backwards-compatibility breaks in
     * PHP.
     */
    public function createForNewestSupportedVersion(): \Flames\Code\Upgrade\ThirdParty\PhpParser\Parser
    {
        return $this->createForVersion(\Flames\Code\Upgrade\ThirdParty\PhpParser\PhpVersion::getNewestSupported());
    }
    /**
     * Create a parser targeting the host PHP version, that is the PHP version we're currently
     * running on. This parser will not use any token emulation.
     */
    public function createForHostVersion(): \Flames\Code\Upgrade\ThirdParty\PhpParser\Parser
    {
        return $this->createForVersion(\Flames\Code\Upgrade\ThirdParty\PhpParser\PhpVersion::getHostVersion());
    }
}
