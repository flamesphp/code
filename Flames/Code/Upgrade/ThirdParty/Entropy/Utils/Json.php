<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Entropy\Utils;

use Flames\Code\Upgrade\ThirdParty\Entropy\Attribute\RelatedTest;
use Flames\Code\Upgrade\ThirdParty\Entropy\Tests\Utils\JsonTest;
use Flames\Code\Upgrade\ThirdParty\Entropy\Validation\Assert;
/**
 * @api to be used outside
 * @see \Entropy\Tests\Utils\JsonTest
 */
final class Json
{
    /**
     * @param mixed $data
     */
    public static function encode($data): string
    {
        $encoded = json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
        Assert::string($encoded);
        return $encoded . \PHP_EOL;
    }
    /**
     * @return array<string, mixed>
     */
    public static function decode(string $json): array
    {
        $decoded = json_decode($json, \true, 512, \JSON_THROW_ON_ERROR);
        Assert::isArray($decoded);
        return $decoded;
    }
}
