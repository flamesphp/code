<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
use Flames\Code\Upgrade\Rules\TypeDeclaration\PHPStan\ObjectTypeSpecifier;
final readonly class ShortClassExpander
{
    /**
     * @see https://regex101.com/r/548EJJ/1
     */
    private const string CLASS_CONST_REGEX = '#::class#';
    public function __construct(private ReflectionProvider $reflectionProvider, private ObjectTypeSpecifier $objectTypeSpecifier)
    {
    }
    /**
     * @api
     */
    public function resolveFqnTargetEntity(string $targetEntity, Node $node): string
    {
        $targetEntity = $this->getCleanedUpTargetEntity($targetEntity);
        if ($this->reflectionProvider->hasClass($targetEntity)) {
            return $targetEntity;
        }
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return $targetEntity;
        }
        $namespacedTargetEntity = $scope->getNamespace() . '\\' . $targetEntity;
        if ($this->reflectionProvider->hasClass($namespacedTargetEntity)) {
            return $namespacedTargetEntity;
        }
        $resolvedType = $this->objectTypeSpecifier->narrowToFullyQualifiedOrAliasedObjectType($node, new ObjectType($targetEntity), $scope);
        if ($resolvedType instanceof ShortenedObjectType) {
            return $resolvedType->getFullyQualifiedName();
        }
        // probably tested class
        return $targetEntity;
    }
    private function getCleanedUpTargetEntity(string $targetEntity): string
    {
        return Strings::replace($targetEntity, self::CLASS_CONST_REGEX, '');
    }
}
