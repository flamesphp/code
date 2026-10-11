<?php

declare (strict_types=1);
namespace PHPStan\File;

final class FileFinderResult
{
    /**
     * @var string[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $files;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $onlyFiles;
    /**
     * @param string[] $files
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $files,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $onlyFiles
    )
    {
        $this->files = $files;
        $this->onlyFiles = $onlyFiles;
    }
    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
    public function isOnlyFiles(): bool
    {
        return $this->onlyFiles;
    }
}
