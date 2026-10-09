<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\PhpParser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpParser\PhpParserNodeMapperInterface;
/**
 * @implements PhpParserNodeMapperInterface<UnionType>
 */
final readonly class UnionTypeNodeMapper implements PhpParserNodeMapperInterface
{
    public function __construct(private TypeFactory $typeFactory, private \Flames\Code\Upgrade\StaticTypeMapper\PhpParser\FullyQualifiedNodeMapper $fullyQualifiedNodeMapper, private \Flames\Code\Upgrade\StaticTypeMapper\PhpParser\NameNodeMapper $nameNodeMapper, private \Flames\Code\Upgrade\StaticTypeMapper\PhpParser\IdentifierNodeMapper $identifierNodeMapper, private \Flames\Code\Upgrade\StaticTypeMapper\PhpParser\IntersectionTypeNodeMapper $intersectionTypeNodeMapper)
    {
    }
    public function getNodeType(): string
    {
        return UnionType::class;
    }
    /**
     * @param UnionType $node
     */
    public function mapToPHPStan(Node $node): Type
    {
        $types = [];
        foreach ($node->types as $unionedType) {
            if ($unionedType instanceof FullyQualified) {
                $types[] = $this->fullyQualifiedNodeMapper->mapToPHPStan($unionedType);
                continue;
            }
            if ($unionedType instanceof Name) {
                $types[] = $this->nameNodeMapper->mapToPHPStan($unionedType);
                continue;
            }
            if ($unionedType instanceof Identifier) {
                $types[] = $this->identifierNodeMapper->mapToPHPStan($unionedType);
                continue;
            }
            $types[] = $this->intersectionTypeNodeMapper->mapToPHPStan($unionedType);
        }
        return $this->typeFactory->createMixedPassedOrUnionType($types, \true);
    }
}
