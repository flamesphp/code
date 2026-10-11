<?php

declare (strict_types=1);
namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Type\Type;
final class FunctionSignature
{
    /**
     * @var list<ParameterSignature>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $parameters;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $returnType;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $nativeReturnType;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $variadic;
    /**
     * @param list<ParameterSignature> $parameters
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $parameters,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $returnType,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $nativeReturnType,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $variadic
    )
    {
        $this->parameters = $parameters;
        $this->returnType = $returnType;
        $this->nativeReturnType = $nativeReturnType;
        $this->variadic = $variadic;
    }
    /**
     * @return list<ParameterSignature>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    public function getReturnType(): Type
    {
        return $this->returnType;
    }
    public function getNativeReturnType(): Type
    {
        return $this->nativeReturnType;
    }
    public function isVariadic(): bool
    {
        return $this->variadic;
    }
}
