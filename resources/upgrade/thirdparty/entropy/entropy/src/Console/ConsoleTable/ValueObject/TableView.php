<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Entropy\Console\ConsoleTable\ValueObject;

use FlamesPrefix202610\Entropy\Validation\Assert;
final readonly class TableView
{
    /**
     * @var TableRow[]
     */
    private array $tableRows;
    /**
     * @param TableRow[] $tableRows
     */
    public function __construct(private string $title, private string $label, array $tableRows, private bool $shouldIncludeRelative = \false)
    {
        Assert::allIsInstanceOf($tableRows, TableRow::class);
        $this->tableRows = $tableRows;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getLabel(): string
    {
        return $this->label;
    }
    public function isShouldIncludeRelative(): bool
    {
        return $this->shouldIncludeRelative;
    }
    /**
     * @return TableRow[]
     */
    public function getRows(): array
    {
        return $this->tableRows;
    }
}
