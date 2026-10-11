<?php

declare (strict_types=1);
namespace PHPStan\File;

use function count;
final class FileMonitorResult
{
    /**
     * @var string[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $newFiles;
    /**
     * @var string[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $changedFiles;
    /**
     * @var string[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $deletedFiles;
    /**
     * @param string[] $newFiles
     * @param string[] $changedFiles
     * @param string[] $deletedFiles
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $newFiles,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $changedFiles,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $deletedFiles
    )
    {
        $this->newFiles = $newFiles;
        $this->changedFiles = $changedFiles;
        $this->deletedFiles = $deletedFiles;
    }
    /**
     * @return string[]
     */
    public function getChangedFiles(): array
    {
        return $this->changedFiles;
    }
    public function hasAnyChanges(): bool
    {
        return count($this->newFiles) > 0 || count($this->changedFiles) > 0 || count($this->deletedFiles) > 0;
    }
}
