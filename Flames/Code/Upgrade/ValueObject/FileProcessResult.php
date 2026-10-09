<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ValueObject;

use Flames\Code\Upgrade\ValueObject\Error\SystemError;
use Flames\Code\Upgrade\ValueObject\Reporting\FileDiff;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class FileProcessResult
{
    /**
     * @param SystemError[] $systemErrors
     */
    public function __construct(private array $systemErrors, private ?FileDiff $fileDiff, private bool $hasChanged)
    {
        Assert::allIsInstanceOf($this->systemErrors, SystemError::class);
    }
    /**
     * @return SystemError[]
     */
    public function getSystemErrors(): array
    {
        return $this->systemErrors;
    }
    public function getFileDiff(): ?FileDiff
    {
        return $this->fileDiff;
    }
    public function hasChanged(): bool
    {
        return $this->hasChanged;
    }
}
