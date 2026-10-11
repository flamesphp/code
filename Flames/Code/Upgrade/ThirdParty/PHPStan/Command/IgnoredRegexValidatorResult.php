<?php

declare (strict_types=1);
namespace PHPStan\Command;

final class IgnoredRegexValidatorResult
{
    /**
     * @var array<string, string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $ignoredTypes;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $anchorsInTheMiddle;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $allErrorsIgnored;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $wrongSequence;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $escapedWrongSequence;
    /**
     * @param array<string, string> $ignoredTypes
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $ignoredTypes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $anchorsInTheMiddle,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $allErrorsIgnored,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $wrongSequence = null,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $escapedWrongSequence = null
    )
    {
        $this->ignoredTypes = $ignoredTypes;
        $this->anchorsInTheMiddle = $anchorsInTheMiddle;
        $this->allErrorsIgnored = $allErrorsIgnored;
        $this->wrongSequence = $wrongSequence;
        $this->escapedWrongSequence = $escapedWrongSequence;
    }
    /**
     * @return array<string, string>
     */
    public function getIgnoredTypes(): array
    {
        return $this->ignoredTypes;
    }
    public function hasAnchorsInTheMiddle(): bool
    {
        return $this->anchorsInTheMiddle;
    }
    public function areAllErrorsIgnored(): bool
    {
        return $this->allErrorsIgnored;
    }
    public function getWrongSequence(): ?string
    {
        return $this->wrongSequence;
    }
    public function getEscapedWrongSequence(): ?string
    {
        return $this->escapedWrongSequence;
    }
}
