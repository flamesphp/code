<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\CodeQuality\Utils;

use FlamesPrefix202610\Nette\Utils\Strings;
/**
 * @api used by rector-drupal
 * @see \Flames\Code\Upgrade\Doctrine\Tests\CodeQuality\Utils\CaseStringHelperTest
 */
final class CaseStringHelper
{
    public static function camelCase(string $value): string
    {
        $spacedValue = str_replace('_', ' ', $value);
        $uppercasedWords = ucwords($spacedValue);
        $spacelessWords = str_replace(' ', '', $uppercasedWords);
        $lowercasedValue = lcfirst($spacelessWords);
        return Strings::replace($lowercasedValue, '#\W#', '');
    }
}
