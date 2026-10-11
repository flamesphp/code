<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser;

/**
 * Modifiers used (as a bit mask) by various flags subnodes, for example on classes, functions,
 * properties and constants.
 */
final class Modifiers
{
    public const int PUBLIC = 1;
    public const int PROTECTED = 2;
    public const int PRIVATE = 4;
    public const int STATIC = 8;
    public const int ABSTRACT = 16;
    public const int FINAL = 32;
    public const int READONLY = 64;
    public const int PUBLIC_SET = 128;
    public const int PROTECTED_SET = 256;
    public const int PRIVATE_SET = 512;
    public const VISIBILITY_MASK = self::PUBLIC | self::PROTECTED | self::PRIVATE;
    public const VISIBILITY_SET_MASK = self::PUBLIC_SET | self::PROTECTED_SET | self::PRIVATE_SET;
    private const array TO_STRING_MAP = [self::PUBLIC => 'public', self::PROTECTED => 'protected', self::PRIVATE => 'private', self::STATIC => 'static', self::ABSTRACT => 'abstract', self::FINAL => 'final', self::READONLY => 'readonly', self::PUBLIC_SET => 'public(set)', self::PROTECTED_SET => 'protected(set)', self::PRIVATE_SET => 'private(set)'];
    public static function toString(int $modifier): string
    {
        if (!isset(self::TO_STRING_MAP[$modifier])) {
            throw new \InvalidArgumentException("Unknown modifier {$modifier}");
        }
        return self::TO_STRING_MAP[$modifier];
    }
    private static function isValidModifier(int $modifier): bool
    {
        $isPow2 = ($modifier & $modifier - 1) == 0 && $modifier != 0;
        return $isPow2 && $modifier <= self::PRIVATE_SET;
    }
    /**
     * @internal
     */
    public static function verifyClassModifier(int $a, int $b): void
    {
        assert(self::isValidModifier($b));
        if (($a & $b) != 0) {
            throw new \Flames\Code\Upgrade\ThirdParty\PhpParser\Error('Multiple ' . self::toString($b) . ' modifiers are not allowed');
        }
        if ($a & 48 && $b & 48) {
            throw new \Flames\Code\Upgrade\ThirdParty\PhpParser\Error('Cannot use the final modifier on an abstract class');
        }
    }
    /**
     * @internal
     */
    public static function verifyModifier(int $a, int $b): void
    {
        assert(self::isValidModifier($b));
        if ($a & \Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers::VISIBILITY_MASK && $b & \Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers::VISIBILITY_MASK || $a & \Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers::VISIBILITY_SET_MASK && $b & \Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers::VISIBILITY_SET_MASK) {
            throw new \Flames\Code\Upgrade\ThirdParty\PhpParser\Error('Multiple access type modifiers are not allowed');
        }
        if (($a & $b) != 0) {
            throw new \Flames\Code\Upgrade\ThirdParty\PhpParser\Error('Multiple ' . self::toString($b) . ' modifiers are not allowed');
        }
        if ($a & 48 && $b & 48) {
            throw new \Flames\Code\Upgrade\ThirdParty\PhpParser\Error('Cannot use the final modifier on an abstract class member');
        }
    }
}
