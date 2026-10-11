<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Type\Type;
final class MethodPrototypeReflection implements \PHPStan\Reflection\ClassMemberReflection
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $name;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public \PHPStan\Reflection\ClassReflection $declaringClass;
    private bool $isStatic;
    private bool $isPrivate;
    private bool $isPublic;
    private bool $isAbstract;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isInternal;
    /**
     * @var ParametersAcceptor[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $variants;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $tentativeReturnType;
    /**
     * @param ParametersAcceptor[] $variants
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $name,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        \PHPStan\Reflection\ClassReflection $declaringClass,
        bool $isStatic,
        bool $isPrivate,
        bool $isPublic,
        bool $isAbstract,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isInternal,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $variants,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $tentativeReturnType
    )
    {
        $this->name = $name;
        $this->declaringClass = $declaringClass;
        $this->isStatic = $isStatic;
        $this->isPrivate = $isPrivate;
        $this->isPublic = $isPublic;
        $this->isAbstract = $isAbstract;
        $this->isInternal = $isInternal;
        $this->variants = $variants;
        $this->tentativeReturnType = $tentativeReturnType;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getDeclaringClass(): \PHPStan\Reflection\ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic(): bool
    {
        return $this->isStatic;
    }
    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }
    public function isPublic(): bool
    {
        return $this->isPublic;
    }
    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }
    public function isInternal(): bool
    {
        return $this->isInternal;
    }
    public function getDocComment(): ?string
    {
        return null;
    }
    /**
     * @return ParametersAcceptor[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }
    public function getTentativeReturnType(): ?Type
    {
        return $this->tentativeReturnType;
    }
}
