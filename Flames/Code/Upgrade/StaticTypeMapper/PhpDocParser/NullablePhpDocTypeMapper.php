<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\PhpDocParser;

use PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpDocParser\PhpDocTypeMapperInterface;
/**
 * @implements PhpDocTypeMapperInterface<NullableTypeNode>
 */
final readonly class NullablePhpDocTypeMapper implements PhpDocTypeMapperInterface
{
    public function __construct(private \Flames\Code\Upgrade\StaticTypeMapper\PhpDocParser\IdentifierPhpDocTypeMapper $identifierPhpDocTypeMapper, private TypeNodeResolver $typeNodeResolver)
    {
    }
    public function getNodeType(): string
    {
        return NullableTypeNode::class;
    }
    /**
     * @param NullableTypeNode $typeNode
     */
    public function mapToPHPStanType(TypeNode $typeNode, Node $node, NameScope $nameScope): Type
    {
        if ($typeNode->type instanceof IdentifierTypeNode) {
            $type = $this->identifierPhpDocTypeMapper->mapToPHPStanType($typeNode->type, $node, $nameScope);
            if ($type instanceof UnionType) {
                return new UnionType(array_merge([new NullType()], $type->getTypes()));
            }
            return new UnionType([new NullType(), $type]);
        }
        // fallback to PHPStan resolver
        return $this->typeNodeResolver->resolve($typeNode, $nameScope);
    }
}
