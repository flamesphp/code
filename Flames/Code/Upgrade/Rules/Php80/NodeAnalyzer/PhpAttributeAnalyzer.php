<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer;

use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpAttribute\Enum\DocTagNodeState;
final readonly class PhpAttributeAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Param $node
     */
    public function hasPhpAttribute($node, string $attributeClass): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if (!$this->nodeNameResolver->isName($attribute->name, $attributeClass)) {
                    continue;
                }
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param string[] $attributeClasses
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Param $node
     */
    public function hasPhpAttributes($node, array $attributeClasses): bool
    {
        $found = array_any($attributeClasses, fn($attributeClass) => $this->hasPhpAttribute($node, $attributeClass));
        return $found;
    }
    /**
     * @param AttributeGroup[] $attributeGroups
     */
    public function hasRemoveArrayState(array $attributeGroups): bool
    {
        foreach ($attributeGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attribute) {
                $args = $attribute->args;
                if ($this->hasArgWithRemoveArrayValue($args)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    /**
     * @param Arg[] $args
     */
    private function hasArgWithRemoveArrayValue(array $args): bool
    {
        foreach ($args as $arg) {
            if (!$arg->value instanceof Array_) {
                continue;
            }
            foreach ($arg->value->items as $item) {
                if (!$item instanceof ArrayItem) {
                    continue;
                }
                if ($item->value instanceof String_ && $item->value->value === DocTagNodeState::REMOVE_ARRAY) {
                    return \true;
                }
            }
        }
        return \false;
    }
}
