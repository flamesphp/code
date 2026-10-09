<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpParser\PhpParserNodeMapperInterface;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ParentObjectWithoutClassType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ParentStaticType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\SelfStaticType;
/**
 * @implements PhpParserNodeMapperInterface<Name>
 */
final readonly class NameNodeMapper implements PhpParserNodeMapperInterface
{
    public function __construct(private ReflectionResolver $reflectionResolver, private \Flames\Code\Upgrade\StaticTypeMapper\PhpParser\FullyQualifiedNodeMapper $fullyQualifiedNodeMapper)
    {
    }
    public function getNodeType(): string
    {
        return Name::class;
    }
    /**
     * @param Name $node
     */
    public function mapToPHPStan(Node $node): Type
    {
        $name = $node->toString();
        if ($node->isSpecialClassName()) {
            return $this->createClassReferenceType($node, $name);
        }
        $expandedNamespacedName = $this->expandedNamespacedName($node);
        if ($expandedNamespacedName instanceof FullyQualified) {
            return $this->fullyQualifiedNodeMapper->mapToPHPStan($expandedNamespacedName);
        }
        return new MixedType();
    }
    private function expandedNamespacedName(Name $name): ?FullyQualified
    {
        if ($name::class !== Name::class) {
            return null;
        }
        if (!$name->hasAttribute(AttributeKey::NAMESPACED_NAME)) {
            return null;
        }
        return new FullyQualified($name->getAttribute(AttributeKey::NAMESPACED_NAME));
    }
    /**
     * @return \PHPStan\Type\MixedType|\PHPStan\Type\StaticType|\Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\SelfStaticType|\PHPStan\Type\ObjectWithoutClassType
     */
    private function createClassReferenceType(Name $name, string $reference)
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($name);
        if (!$classReflection instanceof ClassReflection) {
            return new MixedType();
        }
        if ($reference === ObjectReference::STATIC) {
            return new StaticType($classReflection);
        }
        if ($reference === ObjectReference::SELF) {
            return new SelfStaticType($classReflection);
        }
        $parentClassReflection = $classReflection->getParentClass();
        if ($parentClassReflection instanceof ClassReflection) {
            return new ParentStaticType($parentClassReflection);
        }
        return new ParentObjectWithoutClassType();
    }
}
