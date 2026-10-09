<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Testing\PHPUnit\ValueObject;

use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Util\UpgradeClassesSorter;
use Flames\Code\Upgrade\ValueObject\ProcessResult;
/**
 * @api used in tests
 */
final readonly class UpgradeTestResult
{
    public function __construct(private string $changedContents, private ProcessResult $processResult)
    {
    }
    public function getChangedContents(): string
    {
        return $this->changedContents;
    }
    /**
     * @return array<class-string<RectorInterface>>
     */
    public function getAppliedRectorClasses(): array
    {
        $rectorClasses = [];
        foreach ($this->processResult->getFileDiffs(\false) as $fileDiff) {
            $rectorClasses = array_merge($rectorClasses, $fileDiff->getRectorClasses());
        }
        return UpgradeClassesSorter::sortAndFilterOutPostRectors($rectorClasses);
    }
}
