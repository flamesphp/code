<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Enum\MappingClass;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
/**
 * @api
 */
final readonly class AttributeFinder
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param MappingClass::* $attributeClass
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findAttributeByClassArgByName($node, string $attributeClass, string $argName): ?Expr
    {
        return $this->findAttributeByClassesArgByName($node, [$attributeClass], $argName);
    }
    /**
     * @param string[] $attributeClasses
     * @param string[] $argNames
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findAttributeByClassesArgByNames($node, array $attributeClasses, array $argNames): ?Expr
    {
        $attribute = $this->findAttributeByClasses($node, $attributeClasses);
        if (!$attribute instanceof Attribute) {
            return null;
        }
        foreach ($argNames as $argName) {
            $argExpr = $this->findArgByName($attribute, $argName);
            if ($argExpr instanceof Expr) {
                return $argExpr;
            }
        }
        return null;
    }
    /**
     * @param string[] $attributeClasses
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findAttributeByClassesArgByName($node, array $attributeClasses, string $argName): ?Expr
    {
        $attribute = $this->findAttributeByClasses($node, $attributeClasses);
        if (!$attribute instanceof Attribute) {
            return null;
        }
        return $this->findArgByName($attribute, $argName);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findAttributeByClass($node, string $attributeClass): ?Attribute
    {
        /** @var AttributeGroup $attrGroup */
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if (!$attribute->name instanceof Name) {
                    continue;
                }
                if ($this->nodeNameResolver->isName($attribute->name, $attributeClass)) {
                    return $attribute;
                }
            }
        }
        return null;
    }
    /**
     * @return Attribute[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findManyByClass($node, string $attributeClass): array
    {
        $attributes = [];
        /** @var AttributeGroup $attrGroup */
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if (!$attribute->name instanceof Name) {
                    continue;
                }
                if ($this->nodeNameResolver->isName($attribute->name, $attributeClass)) {
                    $attributes[] = $attribute;
                }
            }
        }
        return $attributes;
    }
    /**
     * @param string[] $attributeClasses
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findAttributeByClasses($node, array $attributeClasses): ?Attribute
    {
        foreach ($attributeClasses as $attributeClass) {
            $attribute = $this->findAttributeByClass($node, $attributeClass);
            if ($attribute instanceof Attribute) {
                return $attribute;
            }
        }
        return null;
    }
    /**
     * @param string[] $attributeClasses
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function hasAttributeByClasses($node, array $attributeClasses): bool
    {
        return $this->findAttributeByClasses($node, $attributeClasses) instanceof Attribute;
    }
    /**
     * @param string[] $names
     * @return Attribute[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findManyByClasses($node, array $names): array
    {
        $attributes = [];
        foreach ($names as $name) {
            $justFoundAttributes = $this->findManyByClass($node, $name);
            $attributes = array_merge($attributes, $justFoundAttributes);
        }
        return $attributes;
    }
    private function findArgByName(Attribute $attribute, string $argName): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr
    {
        foreach ($attribute->args as $arg) {
            if (!$arg->name instanceof Identifier) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($arg->name, $argName)) {
                continue;
            }
            return $arg->value;
        }
        return null;
    }
}
