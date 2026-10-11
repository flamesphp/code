<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\StaticTypeMapper\Resolver\ClassNameFromObjectTypeResolver;
final readonly class ArrayCallableToMethodCallFactory
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function create(Array_ $array): ?MethodCall
    {
        if (count($array->items) !== 2) {
            return null;
        }
        $firstItem = $array->items[0];
        $secondItem = $array->items[1];
        if (!$firstItem instanceof ArrayItem) {
            return null;
        }
        if (!$secondItem instanceof ArrayItem) {
            return null;
        }
        if (!$secondItem->value instanceof String_) {
            return null;
        }
        if (!$firstItem->value instanceof PropertyFetch && !$firstItem->value instanceof Variable) {
            return null;
        }
        $firstItemType = $this->nodeTypeResolver->getType($firstItem->value);
        $className = ClassNameFromObjectTypeResolver::resolve($firstItemType);
        if ($className === null) {
            return null;
        }
        $string = $secondItem->value;
        $methodName = $string->value;
        return new MethodCall($firstItem->value, $methodName);
    }
}
