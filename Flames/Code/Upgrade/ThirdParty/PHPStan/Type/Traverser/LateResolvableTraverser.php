<?php

declare (strict_types=1);
namespace PHPStan\Type\Traverser;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverserCallable;
#[ReferencedByTurboExtension('lateResolvableTraverser')]
final class LateResolvableTraverser implements TypeTraverserCallable
{
    /**
     * @readonly
     */
    private bool $resolveUnresolvableTypes;
    private int $ignoreResolveUnresolvableTypesLevel;
    public function __construct(bool $resolveUnresolvableTypes)
    {
        $this->resolveUnresolvableTypes = $resolveUnresolvableTypes;
        $this->ignoreResolveUnresolvableTypesLevel = 0;
    }
    /**
     * @param callable(Type): Type $traverse
     */
    public function traverse(Type $type, callable $traverse): Type
    {
        while ($type instanceof LateResolvableType && ($this->resolveUnresolvableTypes && $this->ignoreResolveUnresolvableTypesLevel === 0 || $type->isResolvable())) {
            $type = $type->resolve();
        }
        if ($type instanceof CallableType || $type instanceof ClosureType) {
            $this->ignoreResolveUnresolvableTypesLevel++;
            $result = $traverse($type);
            $this->ignoreResolveUnresolvableTypesLevel--;
            return $result;
        }
        return $traverse($type);
    }
}
