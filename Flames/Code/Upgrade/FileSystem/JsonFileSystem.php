<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\FileSystem;

use Flames\Code\Upgrade\ThirdParty\Nette\FileSystem;
use Flames\Code\Upgrade\ThirdParty\Nette\Json;
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
