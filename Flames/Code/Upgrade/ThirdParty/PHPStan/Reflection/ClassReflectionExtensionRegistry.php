<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsMethodsClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsPropertiesClassReflectionExtension;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('classReflectionExtensionRegistry')]
final class ClassReflectionExtensionRegistry
{
    /**
     * @var PropertiesClassReflectionExtension[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $propertiesClassReflectionExtensions;
    /**
     * @var MethodsClassReflectionExtension[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $methodsClassReflectionExtensions;
    /**
     * @var AllowedSubTypesClassReflectionExtension[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $allowedSubTypesClassReflectionExtensions;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public RequireExtendsPropertiesClassReflectionExtension $requireExtendsPropertiesClassReflectionExtension;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public RequireExtendsMethodsClassReflectionExtension $requireExtendsMethodsClassReflectionExtension;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PhpClassReflectionExtension $phpClassReflectionExtension;
    /**
     * @param PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
     * @param MethodsClassReflectionExtension[] $methodsClassReflectionExtensions
     * @param AllowedSubTypesClassReflectionExtension[] $allowedSubTypesClassReflectionExtensions
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $propertiesClassReflectionExtensions,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $methodsClassReflectionExtensions,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $allowedSubTypesClassReflectionExtensions,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        RequireExtendsPropertiesClassReflectionExtension $requireExtendsPropertiesClassReflectionExtension,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        RequireExtendsMethodsClassReflectionExtension $requireExtendsMethodsClassReflectionExtension,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpClassReflectionExtension $phpClassReflectionExtension
    )
    {
        $this->propertiesClassReflectionExtensions = $propertiesClassReflectionExtensions;
        $this->methodsClassReflectionExtensions = $methodsClassReflectionExtensions;
        $this->allowedSubTypesClassReflectionExtensions = $allowedSubTypesClassReflectionExtensions;
        $this->requireExtendsPropertiesClassReflectionExtension = $requireExtendsPropertiesClassReflectionExtension;
        $this->requireExtendsMethodsClassReflectionExtension = $requireExtendsMethodsClassReflectionExtension;
        $this->phpClassReflectionExtension = $phpClassReflectionExtension;
    }
    /**
     * @return PropertiesClassReflectionExtension[]
     */
    public function getPropertiesClassReflectionExtensions(): array
    {
        return $this->propertiesClassReflectionExtensions;
    }
    /**
     * @return MethodsClassReflectionExtension[]
     */
    public function getMethodsClassReflectionExtensions(): array
    {
        return $this->methodsClassReflectionExtensions;
    }
    /**
     * @return AllowedSubTypesClassReflectionExtension[]
     */
    public function getAllowedSubTypesClassReflectionExtensions(): array
    {
        return $this->allowedSubTypesClassReflectionExtensions;
    }
    public function getRequireExtendsPropertyClassReflectionExtension(): RequireExtendsPropertiesClassReflectionExtension
    {
        return $this->requireExtendsPropertiesClassReflectionExtension;
    }
    public function getRequireExtendsMethodsClassReflectionExtension(): RequireExtendsMethodsClassReflectionExtension
    {
        return $this->requireExtendsMethodsClassReflectionExtension;
    }
    public function getPhpClassReflectionExtension(): PhpClassReflectionExtension
    {
        return $this->phpClassReflectionExtension;
    }
}
