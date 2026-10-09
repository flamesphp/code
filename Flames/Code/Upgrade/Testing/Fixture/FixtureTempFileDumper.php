<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Testing\Fixture;

use FlamesPrefix202610\Nette\Utils\FileSystem;
/**
 * @api used in tests
 */
final class FixtureTempFileDumper
{
    public const string TEMP_FIXTURE_DIRECTORY = '/rector/tests_fixture_';
    public static function dump(string $fileContents, string $suffix = 'php'): string
    {
        // the "php" suffix is important, because that will hook into \Flames\Code\Upgrade\Application\FileProcessor\PhpFileProcessor
        $temporaryFileName = sys_get_temp_dir() . self::TEMP_FIXTURE_DIRECTORY . '/' . md5($fileContents) . '.' . $suffix;
        FileSystem::write($temporaryFileName, $fileContents, null);
        return $temporaryFileName;
    }
}
