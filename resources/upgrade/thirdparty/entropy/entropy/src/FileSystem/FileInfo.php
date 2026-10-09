<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Entropy\FileSystem;

use FlamesPrefix202610\Entropy\Utils\FileSystem;
use SplFileInfo;
final class FileInfo extends SplFileInfo
{
    public function __construct(string $filePath, private readonly string $relativePath = '', private readonly string $relativePathname = '')
    {
        parent::__construct($filePath);
    }
    /**
     * @api
     */
    public function getContents(): string
    {
        return FileSystem::read($this->getPathname());
    }
    /**
     * @api
     */
    public function getRelativePath(): string
    {
        return $this->relativePath;
    }
    /**
     * @api
     */
    public function getRelativePathname(): string
    {
        return $this->relativePathname;
    }
}
