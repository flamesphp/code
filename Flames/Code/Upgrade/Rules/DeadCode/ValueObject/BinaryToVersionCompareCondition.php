<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\ValueObject;

use Flames\Code\Upgrade\Rules\DeadCode\Contract\ConditionInterface;
final class BinaryToVersionCompareCondition implements ConditionInterface
{
    /**
     * @param mixed $expectedValue
     */
    public function __construct(
        private readonly \Flames\Code\Upgrade\Rules\DeadCode\ValueObject\VersionCompareCondition $versionCompareCondition,
        private readonly string $binaryClass,
        /**
         * @readonly
         */
        private $expectedValue
    )
    {
    }
    public function getVersionCompareCondition(): \Flames\Code\Upgrade\Rules\DeadCode\ValueObject\VersionCompareCondition
    {
        return $this->versionCompareCondition;
    }
    public function getBinaryClass(): string
    {
        return $this->binaryClass;
    }
    /**
     * @return mixed
     */
    public function getExpectedValue()
    {
        return $this->expectedValue;
    }
}
