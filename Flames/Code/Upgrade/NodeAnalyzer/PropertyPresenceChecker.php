<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Rules\CodeQuality\ValueObject\DefinedPropertyWithType;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PromotedPropertyResolver;
use Flames\Code\Upgrade\PostRector\ValueObject\PropertyMetadata;
/**
 * Can be local property, parent property etc.
 */
final readonly class PropertyPresenceChecker
{
    public function __construct(private PromotedPropertyResolver $promotedPropertyResolver, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * Includes parent classes and traits
     */
    public function hasClassContextProperty(Class_ $class, DefinedPropertyWithType $definedPropertyWithType): bool
    {
        $propertyOrParam = $this->getClassContextProperty($class, $definedPropertyWithType);
        return $propertyOrParam !== null;
    }
    /**
     * @param \Flames\Code\Upgrade\Rules\CodeQuality\ValueObject\DefinedPropertyWithType|\Flames\Code\Upgrade\PostRector\ValueObject\PropertyMetadata $definedPropertyWithType
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param|null
     */
    public function getClassContextProperty(Class_ $class, $definedPropertyWithType)
    {
        $className = $this->nodeNameResolver->getName($class);
        if ($className === null) {
            return null;
        }
        $property = $class->getProperty($definedPropertyWithType->getName());
        if ($property instanceof Property) {
            return $property;
        }
        $promotedPropertyParams = $this->promotedPropertyResolver->resolveFromClass($class);
        foreach ($promotedPropertyParams as $promotedPropertyParam) {
            if ($this->nodeNameResolver->isName($promotedPropertyParam, $definedPropertyWithType->getName())) {
                return $promotedPropertyParam;
            }
        }
        return null;
    }
}
