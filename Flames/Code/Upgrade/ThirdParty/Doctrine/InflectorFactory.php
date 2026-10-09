<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine;

use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\English;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Esperanto;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\French;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Italian;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\NorwegianBokmal;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Portuguese;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Spanish;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Turkish;
use InvalidArgumentException;
use function sprintf;
final class InflectorFactory
{
    public static function create(): LanguageInflectorFactory
    {
        return self::createForLanguage(Language::ENGLISH);
    }
    public static function createForLanguage(string $language): LanguageInflectorFactory
    {
        return match ($language) {
            Language::ENGLISH => new English\InflectorFactory(),
            Language::ESPERANTO => new Esperanto\InflectorFactory(),
            Language::FRENCH => new French\InflectorFactory(),
            Language::ITALIAN => new Italian\InflectorFactory(),
            Language::NORWEGIAN_BOKMAL => new NorwegianBokmal\InflectorFactory(),
            Language::PORTUGUESE => new Portuguese\InflectorFactory(),
            Language::SPANISH => new Spanish\InflectorFactory(),
            Language::TURKISH => new Turkish\InflectorFactory(),
            default => throw new InvalidArgumentException(sprintf('Language "%s" is not supported.', $language)),
        };
    }
}
