<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpParser\PhpParserNodeMapperInterface;
/**
 * @implements PhpParserNodeMapperInterface<NullableType>
 */
final readonly class NullableTypeNodeMapper implements PhpParserNodeMapperInterface
{
    public function __construct(private TypeFactory $typeFactory, private \Flames\Code\Upgrade\StaticTypeMapper\PhpParser\FullyQualifiedNodeMapper $fullyQualifiedNodeMapper, private \Flames\Code\Upgrade\StaticTypeMapper\PhpParser\NameNodeMapper $nameNodeMapper, private \Flames\Code\Upgrade\StaticTypeMapper\PhpParser\IdentifierNodeMapper $identifierNodeMapper)
    {
    }
    public function getNodeType(): string
    {
        return NullableType::class;
    }
    /**
     * @param NullableType $node
     */
    public function mapToPHPStan(Node $node): Type
    {
        if ($node->type instanceof FullyQualified) {
            $type = $this->fullyQualifiedNodeMapper->mapToPHPStan($node->type);
        } elseif ($node->type instanceof Name) {
            $type = $this->nameNodeMapper->mapToPHPStan($node->type);
        } else {
            $type = $this->identifierNodeMapper->mapToPHPStan($node->type);
        }
        $types = [$type, new NullType()];
        return $this->typeFactory->createMixedPassedOrUnionType($types);
    }
}
