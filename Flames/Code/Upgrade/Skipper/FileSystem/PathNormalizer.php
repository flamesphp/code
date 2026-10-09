<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Skipper\FileSystem;

final class PathNormalizer
{
    public static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
