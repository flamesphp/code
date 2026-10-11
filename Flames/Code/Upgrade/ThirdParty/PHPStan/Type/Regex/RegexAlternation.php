<?php

declare (strict_types=1);
namespace PHPStan\Type\Regex;

use function array_key_exists;
final class RegexAlternation
{
    /**
     * @readonly
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $alternationId;
    /**
     * @readonly
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $alternationsCount;
    /** @var array<int, list<int>> */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $groupCombinations = [];
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $alternationId,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $alternationsCount
    )
    {
        $this->alternationId = $alternationId;
        $this->alternationsCount = $alternationsCount;
    }
    public function getId(): int
    {
        return $this->alternationId;
    }
    public function pushGroup(int $combinationIndex, \PHPStan\Type\Regex\RegexCapturingGroup $group): void
    {
        if (!array_key_exists($combinationIndex, $this->groupCombinations)) {
            $this->groupCombinations[$combinationIndex] = [];
        }
        $this->groupCombinations[$combinationIndex][] = $group->id;
    }
    public function getAlternationsCount(): int
    {
        return $this->alternationsCount;
    }
    /**
     * @return array<int, list<int>>
     */
    public function getGroupCombinations(): array
    {
        return $this->groupCombinations;
    }
}
