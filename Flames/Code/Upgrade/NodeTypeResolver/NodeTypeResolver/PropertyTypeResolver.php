<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Property;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\Contract\NodeTypeResolverInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
/**
 * @see \Flames\Code\Upgrade\Tests\NodeTypeResolver\PerNodeTypeResolver\PropertyTypeResolver\PropertyTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Property>
 */
final readonly class PropertyTypeResolver implements NodeTypeResolverInterface
{
    public function __construct(private \Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver\PropertyFetchTypeResolver $propertyFetchTypeResolver)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeClasses(): array
    {
        return [Property::class];
    }
    /**
     * @param Property $node
     */
    public function resolve(Node $node): Type
    {
        // fake property to local PropertyFetch → PHPStan understands that
        $propertyFetch = new PropertyFetch(new Variable('this'), (string) $node->props[0]->name);
        $propertyFetch->setAttribute(AttributeKey::SCOPE, $node->getAttribute(AttributeKey::SCOPE));
        return $this->propertyFetchTypeResolver->resolve($propertyFetch);
    }
}
