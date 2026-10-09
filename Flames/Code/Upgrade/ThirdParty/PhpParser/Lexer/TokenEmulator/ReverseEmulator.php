<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Lexer\TokenEmulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\PhpVersion;
/**
 * Reverses emulation direction of the inner emulator.
 */
final class ReverseEmulator extends \Flames\Code\Upgrade\ThirdParty\PhpParser\Lexer\TokenEmulator\TokenEmulator
{
    public function __construct(
        /** @var TokenEmulator Inner emulator */
        private readonly \Flames\Code\Upgrade\ThirdParty\PhpParser\Lexer\TokenEmulator\TokenEmulator $emulator
    )
    {
    }
    public function getPhpVersion(): PhpVersion
    {
        return $this->emulator->getPhpVersion();
    }
    public function isEmulationNeeded(string $code): bool
    {
        return $this->emulator->isEmulationNeeded($code);
    }
    public function emulate(string $code, array $tokens): array
    {
        return $this->emulator->reverseEmulate($code, $tokens);
    }
    public function reverseEmulate(string $code, array $tokens): array
    {
        return $this->emulator->emulate($code, $tokens);
    }
    public function preprocessCode(string $code, array &$patches): string
    {
        return $code;
    }
}
