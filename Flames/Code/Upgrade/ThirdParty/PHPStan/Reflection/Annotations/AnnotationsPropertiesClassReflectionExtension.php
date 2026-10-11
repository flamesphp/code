<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Annotations;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\NeverType;
// autoTag: false - wired explicitly in ClassReflectionExtensionRegistry, must not be tagged
#[AutowiredService(\null, \null, \true, \false)]
final class AnnotationsPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    /** @var ExtendedPropertyReflection[][] */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $properties = [];
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (!isset($this->properties[$classReflection->getCacheKey()][$propertyName])) {
            $property = $this->findClassReflectionWithProperty($classReflection, $classReflection, $propertyName);
            if ($property === null) {
                return \false;
            }
            $this->properties[$classReflection->getCacheKey()][$propertyName] = $property;
        }
        return \true;
    }
    public function getProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
    {
        return $this->properties[$classReflection->getCacheKey()][$propertyName];
    }
    private function findClassReflectionWithProperty(ClassReflection $classReflection, ClassReflection $declaringClass, string $propertyName): ?ExtendedPropertyReflection
    {
        $propertyTags = $classReflection->getPropertyTags();
        if (isset($propertyTags[$propertyName])) {
            $propertyTag = $propertyTags[$propertyName];
            $isReadable = $propertyTags[$propertyName]->readableType !== null;
            $isWritable = $propertyTags[$propertyName]->writableType !== null;
            if ($classReflection->hasNativeProperty($propertyName)) {
                $nativeProperty = $classReflection->getNativeProperty($propertyName);
                if (!$nativeProperty->isPrivate() && !$nativeProperty->isStatic()) {
                    $isReadable = $isReadable || $nativeProperty->isReadable();
                    $isWritable = $isWritable || $nativeProperty->isWritable();
                }
            }
            return new \PHPStan\Reflection\Annotations\AnnotationPropertyReflection($propertyName, $declaringClass, TemplateTypeHelper::resolveTemplateTypes($propertyTag->readableType ?? new NeverType(), $classReflection->getActiveTemplateTypeMap(), $classReflection->getCallSiteVarianceMap(), TemplateTypeVariance::createCovariant()), TemplateTypeHelper::resolveTemplateTypes($propertyTag->writableType ?? new NeverType(), $classReflection->getActiveTemplateTypeMap(), $classReflection->getCallSiteVarianceMap(), TemplateTypeVariance::createContravariant()), $isReadable, $isWritable);
        }
        foreach ($classReflection->getTraits() as $traitClass) {
            $methodWithDeclaringClass = $this->findClassReflectionWithProperty($traitClass, $classReflection, $propertyName);
            if ($methodWithDeclaringClass === null) {
                continue;
            }
            return $methodWithDeclaringClass;
        }
        $parentClass = $classReflection->getParentClass();
        if ($parentClass !== null) {
            $methodWithDeclaringClass = $this->findClassReflectionWithProperty($parentClass, $parentClass, $propertyName);
            if ($methodWithDeclaringClass !== null) {
                return $methodWithDeclaringClass;
            }
        }
        foreach ($classReflection->getInterfaces() as $interfaceClass) {
            $methodWithDeclaringClass = $this->findClassReflectionWithProperty($interfaceClass, $interfaceClass, $propertyName);
            if ($methodWithDeclaringClass === null) {
                continue;
            }
            return $methodWithDeclaringClass;
        }
        return null;
    }
}
