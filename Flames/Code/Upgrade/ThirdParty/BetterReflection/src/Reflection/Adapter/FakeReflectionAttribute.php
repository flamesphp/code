<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\Adapter;

use PhpParser\Node\Expr;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
final class FakeReflectionAttribute
{
    private BetterReflectionAttribute $betterReflectionAttribute;
    public function __construct(BetterReflectionAttribute $betterReflectionAttribute)
    {
        $this->betterReflectionAttribute = $betterReflectionAttribute;
    }
    public function getName(): string
    {
        return $this->betterReflectionAttribute->name;
    }
    public function getTarget(): int
    {
        return $this->betterReflectionAttribute->getTarget();
    }
    public function isRepeated(): bool
    {
        return $this->betterReflectionAttribute->isRepeated;
    }
    /**
     * @return array<int|string, mixed>
     */
    public function getArguments(): array
    {
        return $this->betterReflectionAttribute->getArguments();
    }
    /** @return array<int|string, Expr> */
    public function getArgumentsExpressions(): array
    {
        return $this->betterReflectionAttribute->getArgumentsExpressions();
    }
    public function newInstance(): object
    {
        $class = $this->betterReflectionAttribute->getName();
        return new $class(...$this->betterReflectionAttribute->getArguments());
    }
    public function __toString(): string
    {
        return \PHPStan\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast::toString($this->betterReflectionAttribute);
    }
}
