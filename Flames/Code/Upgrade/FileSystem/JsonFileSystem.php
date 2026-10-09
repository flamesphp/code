<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\FileSystem;

use FlamesPrefix202610\Nette\Utils\FileSystem;
use FlamesPrefix202610\Nette\Utils\Json;
final class JsonFileSystem
{
    /**
     * @return array<string, mixed>
     */
    public static function readFilePath(string $filePath): array
    {
        $fileContents = FileSystem::read($filePath);
        return Json::decode($fileContents, \true);
    }
}
