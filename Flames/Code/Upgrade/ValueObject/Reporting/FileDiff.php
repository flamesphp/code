<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ValueObject\Reporting;

use FlamesPrefix202610\Nette\Utils\Strings;
use Flames\Code\Upgrade\ChangesReporting\ValueObject\UpgradeWithLineChange;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Parallel\Contract\SerializableInterface;
use Flames\Code\Upgrade\Parallel\ValueObject\BridgeItem;
use Flames\Code\Upgrade\Util\UpgradeClassesSorter;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Tests\ValueObject\Reporting\FileDiffTest
 */
final readonly class FileDiff implements SerializableInterface
{
    /**
     * @see https://en.wikipedia.org/wiki/Diff#Unified_format
     * @see https://regex101.com/r/AUPIX4/2
     */
    private const string DIFF_HUNK_HEADER_REGEX = '#@@(.*?)(?<' . self::FIRST_LINE_KEY . '>\d+)(,(?<' . self::LINE_RANGE_KEY . '>\d+))?(.*?)@@#';
    private const string FIRST_LINE_KEY = 'first_line';
    private const string LINE_RANGE_KEY = 'line_range';
    /**
     * @param UpgradeWithLineChange[] $rectorsWithLineChanges
     */
    public function __construct(private string $relativeFilePath, private string $diff, private string $diffConsoleFormatted, /**
     * @readonly
     */
    private array $rectorsWithLineChanges = [])
    {
        Assert::allIsInstanceOf($this->rectorsWithLineChanges, UpgradeWithLineChange::class);
    }
    public function getDiff(): string
    {
        return $this->diff;
    }
    public function getDiffConsoleFormatted(): string
    {
        return $this->diffConsoleFormatted;
    }
    public function getRelativeFilePath(): string
    {
        return $this->relativeFilePath;
    }
    public function getAbsoluteFilePath(): ?string
    {
        return \realpath($this->relativeFilePath) ?: null;
    }
    /**
     * @return UpgradeWithLineChange[]
     */
    public function getRectorChanges(): array
    {
        return $this->rectorsWithLineChanges;
    }
    /**
     * @return string[]
     */
    public function getRectorShortClasses(): array
    {
        $rectorShortClasses = [];
        foreach ($this->getRectorClasses() as $rectorClass) {
            $rectorShortClasses[] = (string) Strings::after($rectorClass, '\\', -1);
        }
        return $rectorShortClasses;
    }
    /**
     * @return array<class-string<RectorInterface>>
     */
    public function getRectorClasses(): array
    {
        $rectorClasses = [];
        foreach ($this->rectorsWithLineChanges as $rectorWithLineChange) {
            $rectorClasses[] = $rectorWithLineChange->getRectorClass();
        }
        return UpgradeClassesSorter::sortAndFilterOutPostRectors($rectorClasses);
    }
    public function getFirstLineNumber(): ?int
    {
        $match = Strings::match($this->diff, self::DIFF_HUNK_HEADER_REGEX);
        // probably some error in diff
        if (!isset($match[self::FIRST_LINE_KEY])) {
            return null;
        }
        return (int) $match[self::FIRST_LINE_KEY];
    }
    public function getLastLineNumber(): ?int
    {
        $match = Strings::match($this->diff, self::DIFF_HUNK_HEADER_REGEX);
        $firstLine = $this->getFirstLineNumber();
        // probably some error in diff
        if (!isset($match[self::LINE_RANGE_KEY])) {
            return $firstLine;
        }
        // line range is not mandatory
        if ($match[self::LINE_RANGE_KEY] === '') {
            return $firstLine;
        }
        $lineRange = (int) $match[self::LINE_RANGE_KEY];
        return $firstLine + $lineRange;
    }
    /**
     * @return array{relative_file_path: string, diff: string, diff_console_formatted: string, rectors_with_line_changes: UpgradeWithLineChange[]}
     */
    public function jsonSerialize(): array
    {
        return [BridgeItem::RELATIVE_FILE_PATH => $this->relativeFilePath, BridgeItem::DIFF => $this->diff, BridgeItem::DIFF_CONSOLE_FORMATTED => $this->diffConsoleFormatted, BridgeItem::RECTORS_WITH_LINE_CHANGES => $this->rectorsWithLineChanges];
    }
    /**
     * @param array<string, mixed> $json
     */
    public static function decode(array $json): self
    {
        $rectorWithLineChanges = [];
        foreach ($json[BridgeItem::RECTORS_WITH_LINE_CHANGES] as $rectorWithLineChangesJson) {
            $rectorWithLineChanges[] = UpgradeWithLineChange::decode($rectorWithLineChangesJson);
        }
        return new self($json[BridgeItem::RELATIVE_FILE_PATH], $json[BridgeItem::DIFF], $json[BridgeItem::DIFF_CONSOLE_FORMATTED], $rectorWithLineChanges);
    }
}
