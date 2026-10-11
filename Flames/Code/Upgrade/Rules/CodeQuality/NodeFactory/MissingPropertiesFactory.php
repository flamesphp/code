<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\PropertyItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Rules\CodeQuality\ValueObject\DefinedPropertyWithType;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
final readonly class MissingPropertiesFactory
{
    public function __construct(private \Flames\Code\Upgrade\Rules\CodeQuality\NodeFactory\PropertyTypeDecorator $propertyTypeDecorator, private PhpVersionProvider $phpVersionProvider, private StaticTypeMapper $staticTypeMapper)
    {
    }
    /**
     * @param DefinedPropertyWithType[] $definedPropertiesWithType
     * @return Property[]
     */
    public function create(array $definedPropertiesWithType): array
    {
        $newProperties = [];
        foreach ($definedPropertiesWithType as $definedPropertyWithType) {
            $visibilityModifier = $this->isFromAlwaysDefinedMethod($definedPropertyWithType) ? Modifiers::PRIVATE : Modifiers::PUBLIC;
            $property = new Property($visibilityModifier, [new PropertyItem($definedPropertyWithType->getName())]);
            if ($this->isFromAlwaysDefinedMethod($definedPropertyWithType) && $this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::TYPED_PROPERTIES)) {
                $propertyType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($definedPropertyWithType->getType(), TypeKind::PROPERTY);
                if ($propertyType instanceof Node) {
                    $property->type = $propertyType;
                    $newProperties[] = $property;
                    continue;
                }
            }
            // fallback to docblock
            $this->propertyTypeDecorator->decorateProperty($property, $definedPropertyWithType->getType());
            $newProperties[] = $property;
        }
        return $newProperties;
    }
    private function isFromAlwaysDefinedMethod(DefinedPropertyWithType $definedPropertyWithType): bool
    {
        return in_array($definedPropertyWithType->getDefinedInMethodName(), [MethodName::CONSTRUCT, MethodName::SET_UP], \true);
    }
}
