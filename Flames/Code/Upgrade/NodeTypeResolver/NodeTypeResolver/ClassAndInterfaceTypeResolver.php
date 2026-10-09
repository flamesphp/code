<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Contract\NodeTypeResolverInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
/**
 * @see \Flames\Code\Upgrade\Tests\NodeTypeResolver\PerNodeTypeResolver\ClassAndInterfaceTypeResolver\ClassTypeResolverTest
 * @see \Flames\Code\Upgrade\Tests\NodeTypeResolver\PerNodeTypeResolver\ClassAndInterfaceTypeResolver\InterfaceTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Class_|Interface_>
 */
final readonly class ClassAndInterfaceTypeResolver implements NodeTypeResolverInterface
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeClasses(): array
    {
        return [Class_::class, Interface_::class];
    }
    /**
     * @param Class_|Interface_ $node
     */
    public function resolve(Node $node): Type
    {
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            // new node probably
            return new MixedType();
        }
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return new ObjectType((string) $this->nodeNameResolver->getName($node));
        }
        return new ObjectType($classReflection->getName(), null, $classReflection);
    }
}
