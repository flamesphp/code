<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\Naming\Naming\PropertyNaming;
use Flames\Code\Upgrade\NodeManipulator\PropertyManipulator;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PromotedPropertyResolver;
use Flames\Code\Upgrade\PostRector\ValueObject\PropertyMetadata;
final readonly class DependencyInjectionMethodCallAnalyzer
{
    public function __construct(private PropertyNaming $propertyNaming, private \Flames\Code\Upgrade\Symfony\NodeAnalyzer\ServiceTypeMethodCallResolver $serviceTypeMethodCallResolver, private PromotedPropertyResolver $promotedPropertyResolver, private NodeNameResolver $nodeNameResolver, private PropertyManipulator $propertyManipulator)
    {
    }
    public function replaceMethodCallWithPropertyFetchAndDependency(Class_ $class, MethodCall $methodCall): ?PropertyMetadata
    {
        $serviceType = $this->serviceTypeMethodCallResolver->resolve($methodCall);
        if (!$serviceType instanceof ObjectType) {
            return null;
        }
        $resolvedPropertyNameByType = $this->propertyManipulator->resolveExistingClassPropertyNameByType($class, $serviceType);
        if (is_string($resolvedPropertyNameByType)) {
            $propertyName = $resolvedPropertyNameByType;
        } else {
            $propertyName = $this->propertyNaming->fqnToVariableName($serviceType);
            $propertyName = $this->resolveNewPropertyNameWhenExists($class, $propertyName, $propertyName);
        }
        return new PropertyMetadata($propertyName, $serviceType);
    }
    private function resolveNewPropertyNameWhenExists(Class_ $class, string $originalPropertyName, string $propertyName, int $count = 1): string
    {
        $lastCount = (string) substr($propertyName, strlen($originalPropertyName));
        if (is_numeric($lastCount)) {
            $count = (int) $lastCount;
        }
        $promotedPropertyParams = $this->promotedPropertyResolver->resolveFromClass($class);
        foreach ($promotedPropertyParams as $promotedPropertyParam) {
            if ($this->nodeNameResolver->isName($promotedPropertyParam->var, $propertyName)) {
                $propertyName = $this->resolveIncrementPropertyName($originalPropertyName, $count);
                return $this->resolveNewPropertyNameWhenExists($class, $originalPropertyName, $propertyName, $count);
            }
        }
        $property = $class->getProperty($propertyName);
        if (!$property instanceof Property) {
            return $propertyName;
        }
        $propertyName = $this->resolveIncrementPropertyName($originalPropertyName, $count);
        return $this->resolveNewPropertyNameWhenExists($class, $originalPropertyName, $propertyName, $count);
    }
    private function resolveIncrementPropertyName(string $originalPropertyName, int $count): string
    {
        ++$count;
        return $originalPropertyName . $count;
    }
}
