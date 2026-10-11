<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PHPStan\BetterReflection\Reflection\Adapter\FakeReflectionAttribute;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_last;
use function count;
use function is_int;
#[AutowiredService]
final class AttributeReflectionFactory
{
    private \PHPStan\Reflection\InitializerExprTypeResolver $initializerExprTypeResolver;
    private ReflectionProviderProvider $reflectionProviderProvider;
    public function __construct(\PHPStan\Reflection\InitializerExprTypeResolver $initializerExprTypeResolver, ReflectionProviderProvider $reflectionProviderProvider)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->reflectionProviderProvider = $reflectionProviderProvider;
    }
    /**
     * @param list<ReflectionAttribute|FakeReflectionAttribute> $reflections
     * @return list<AttributeReflection>
     */
    public function fromNativeReflection(array $reflections, \PHPStan\Reflection\InitializerExprContext $context): array
    {
        $attributes = [];
        foreach ($reflections as $reflection) {
            $attribute = $this->fromNameAndArgumentExpressions($reflection->getName(), $reflection->getArgumentsExpressions(), $context);
            if ($attribute === null) {
                continue;
            }
            $attributes[] = $attribute;
        }
        return $attributes;
    }
    /**
     * @param AttributeGroup[] $attrGroups
     * @return list<AttributeReflection>
     */
    public function fromAttrGroups(array $attrGroups, \PHPStan\Reflection\InitializerExprContext $context): array
    {
        $attributes = [];
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $arguments = [];
                foreach ($attr->args as $i => $arg) {
                    if ($arg->name === null) {
                        $argName = $i;
                    } else {
                        $argName = $arg->name->name;
                    }
                    $arguments[$argName] = $arg->value;
                }
                $attributeReflection = $this->fromNameAndArgumentExpressions($attr->name->name, $arguments, $context);
                if ($attributeReflection === null) {
                    continue;
                }
                $attributes[] = $attributeReflection;
            }
        }
        return $attributes;
    }
    /**
     * @param array<int|string, Expr> $arguments
     */
    private function fromNameAndArgumentExpressions(string $name, array $arguments, \PHPStan\Reflection\InitializerExprContext $context): ?\PHPStan\Reflection\AttributeReflection
    {
        if (count($arguments) === 0) {
            return new \PHPStan\Reflection\AttributeReflection($name, []);
        }
        $reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
        if (!$reflectionProvider->hasClass($name)) {
            return null;
        }
        $classReflection = $reflectionProvider->getClass($name);
        if (!$classReflection->hasConstructor()) {
            return null;
        }
        if (!$classReflection->isAttributeClass()) {
            return null;
        }
        $className = $classReflection->getName();
        $nativeConstructor = $classReflection->getNativeReflection()->getConstructor();
        if ($nativeConstructor === null) {
            return null;
        }
        $nativeParameters = $nativeConstructor->getParameters();
        $namedArgTypes = [];
        foreach ($arguments as $i => $argExpr) {
            if (is_int($i)) {
                if (isset($nativeParameters[$i])) {
                    $namedArgTypes[$nativeParameters[$i]->betterReflectionParameter->getName()] = $this->initializerExprTypeResolver->getType($argExpr, $context);
                    continue;
                }
                if (count($nativeParameters) > 0) {
                    $lastParameter = array_last($nativeParameters);
                    if ($lastParameter->betterReflectionParameter->isVariadic()) {
                        $parameterName = $lastParameter->betterReflectionParameter->getName();
                        if (array_key_exists($parameterName, $namedArgTypes)) {
                            $namedArgTypes[$parameterName] = TypeCombinator::union($namedArgTypes[$parameterName], $this->initializerExprTypeResolver->getType($argExpr, $context));
                            continue;
                        }
                        $namedArgTypes[$parameterName] = $this->initializerExprTypeResolver->getType($argExpr, $context);
                    }
                }
                continue;
            }
            foreach ($nativeParameters as $parameter) {
                if ($parameter->betterReflectionParameter->getName() !== $i) {
                    continue;
                }
                $namedArgTypes[$i] = $this->initializerExprTypeResolver->getType($argExpr, $context);
                break;
            }
        }
        return new \PHPStan\Reflection\AttributeReflection($className, $namedArgTypes);
    }
}
