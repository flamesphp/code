<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('closureParameterTypes')]
final class ClosureParameterTypes
{
    /**
     * @readonly
     * @var ParameterReflection[]|null
     */
    public ?array $parameters;
    /**
     * @readonly
     * @var ParameterReflection[]|null
     */
    public ?array $nativeParameters;
    /**
     * @param ParameterReflection[]|null $parameters
     * @param ParameterReflection[]|null $nativeParameters
     */
    public function __construct(?array $parameters, ?array $nativeParameters)
    {
        $this->parameters = $parameters;
        $this->nativeParameters = $nativeParameters;
    }
}
