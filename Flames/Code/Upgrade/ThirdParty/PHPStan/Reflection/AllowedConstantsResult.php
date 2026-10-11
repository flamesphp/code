<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * Result of checking constants passed to a parameter against its allowed set.
 *
 * Returned by ExtendedParameterReflection::checkAllowedConstants(). Reports
 * three kinds of problems: constants not in the allowed list, mutually exclusive
 * constants combined in a bitmask, and bitmask usage on a single-value parameter.
 *
 * @api
 */
#[ReferencedByTurboExtension('allowedConstantsResult')]
final class AllowedConstantsResult
{
    /**
     * @var list<ConstantReflection>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $disallowedConstants;
    /**
     * @var list<list<string>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $violatedExclusiveGroups;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $bitmaskNotAllowed;
    /**
     * @param list<ConstantReflection> $disallowedConstants
     * @param list<list<string>> $violatedExclusiveGroups
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $disallowedConstants,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $violatedExclusiveGroups,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $bitmaskNotAllowed
    )
    {
        $this->disallowedConstants = $disallowedConstants;
        $this->violatedExclusiveGroups = $violatedExclusiveGroups;
        $this->bitmaskNotAllowed = $bitmaskNotAllowed;
    }
    public function isOk(): bool
    {
        return $this->disallowedConstants === [] && $this->violatedExclusiveGroups === [] && !$this->bitmaskNotAllowed;
    }
    public function isBitmaskNotAllowed(): bool
    {
        return $this->bitmaskNotAllowed;
    }
    /**
     * @return list<ConstantReflection>
     */
    public function getDisallowedConstants(): array
    {
        return $this->disallowedConstants;
    }
    /**
     * @return list<list<string>>
     */
    public function getViolatedExclusiveGroups(): array
    {
        return $this->violatedExclusiveGroups;
    }
}
