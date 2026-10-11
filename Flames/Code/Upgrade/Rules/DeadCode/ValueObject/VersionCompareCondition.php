<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\ValueObject;

use Flames\Code\Upgrade\Rules\DeadCode\Contract\ConditionInterface;
final readonly class VersionCompareCondition implements ConditionInterface
{
    public function __construct(private int $firstVersion, private int $secondVersion, private ?string $compareSign)
    {
    }
    public function getFirstVersion(): int
    {
        return $this->firstVersion;
    }
    public function getSecondVersion(): int
    {
        return $this->secondVersion;
    }
    public function getCompareSign(): ?string
    {
        return $this->compareSign;
    }
}
