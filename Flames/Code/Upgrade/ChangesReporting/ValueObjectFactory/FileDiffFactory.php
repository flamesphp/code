<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ChangesReporting\ValueObjectFactory;

use Flames\Code\Upgrade\ChangesReporting\ValueObject\UpgradeWithLineChange;
use Flames\Code\Upgrade\Console\Formatter\ColorConsoleDiffFormatter;
use Flames\Code\Upgrade\Differ\DefaultDiffer;
use Flames\Code\Upgrade\FileSystem\FilePathHelper;
use Flames\Code\Upgrade\ValueObject\Application\File;
use Flames\Code\Upgrade\ValueObject\Reporting\FileDiff;
final readonly class FileDiffFactory
{
    public function __construct(private DefaultDiffer $defaultDiffer, private FilePathHelper $filePathHelper, private ColorConsoleDiffFormatter $colorConsoleDiffFormatter)
    {
    }
    /**
     * @param UpgradeWithLineChange[] $rectorsWithLineChanges
     */
    public function createFileDiffWithLineChanges(bool $shouldShowDiffs, File $file, string $oldContent, string $newContent, array $rectorsWithLineChanges): FileDiff
    {
        $relativeFilePath = $this->filePathHelper->relativePath($file->getFilePath());
        $diff = $shouldShowDiffs ? $this->defaultDiffer->diff($oldContent, $newContent) : '';
        $consoleDiff = $shouldShowDiffs ? $this->colorConsoleDiffFormatter->format($diff) : '';
        // always keep the most recent diff
        return new FileDiff($relativeFilePath, $diff, $consoleDiff, $rectorsWithLineChanges);
    }
}
