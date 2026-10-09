<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Doctrine\Inflector;

use FlamesPrefix202610\Doctrine\Inflector\Rules\English;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Esperanto;
use FlamesPrefix202610\Doctrine\Inflector\Rules\French;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Italian;
use FlamesPrefix202610\Doctrine\Inflector\Rules\NorwegianBokmal;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Portuguese;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Spanish;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Turkish;
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
