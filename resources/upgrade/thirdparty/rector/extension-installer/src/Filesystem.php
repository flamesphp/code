<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\UpgradeInstaller;

interface Filesystem
{
    public function isFile(string $pathToFile): bool;
    public function hashFile(string $pathToFile): string;
    public function hashEquals(string $hash, string $content): bool;
    public function writeFile(string $pathToFile, string $contents): void;
}
