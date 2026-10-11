<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\NodeTypeResolver\Contract\NodeTypeResolverInterface;
/**
 * @see \Flames\Code\Upgrade\Tests\NodeTypeResolver\PerNodeTypeResolver\TraitTypeResolver\TraitTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Trait_>
 */
final readonly class TraitTypeResolver implements NodeTypeResolverInterface
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeClasses(): array
    {
        return [Trait_::class];
    }
    /**
     * @param Trait_ $node
     */
    public function resolve(Node $node): Type
    {
        $traitName = (string) $node->namespacedName;
        if (!$this->reflectionProvider->hasClass($traitName)) {
            return new MixedType();
        }
        $classReflection = $this->reflectionProvider->getClass($traitName);
        $types = [];
        $types[] = new ObjectType($traitName);
        foreach ($classReflection->getTraits() as $usedTraitReflection) {
            $types[] = new ObjectType($usedTraitReflection->getName());
        }
        if (count($types) === 1) {
            return $types[0];
        }
        return new UnionType($types);
    }
}
