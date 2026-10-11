<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Arguments\ValueObject;

use Flames\Code\Upgrade\Rules\Arguments\Contract\ReplaceArgumentDefaultValueInterface;
final class ReplaceFuncCallArgumentDefaultValue implements ReplaceArgumentDefaultValueInterface
{
    /**
     * @param mixed $valueBefore
     * @param mixed $valueAfter
     */
    public function __construct(
        private readonly string $function,
        private readonly int $position,
        /**
         * @readonly
         */
        private $valueBefore,
        /**
         * @readonly
         */
        private $valueAfter
    )
    {
    }
    public function getFunction(): string
    {
        return $this->function;
    }
    public function getPosition(): int
    {
        return $this->position;
    }
    /**
     * @return mixed
     */
    public function getValueBefore()
    {
        return $this->valueBefore;
    }
    /**
     * @return mixed
     */
    public function getValueAfter()
    {
        return $this->valueAfter;
    }
}
