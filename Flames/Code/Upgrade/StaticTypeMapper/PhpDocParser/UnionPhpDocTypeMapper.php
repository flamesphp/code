<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\PhpDocParser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IntersectionTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\UnionTypeNode;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpDocParser\PhpDocTypeMapperInterface;
/**
 * @implements PhpDocTypeMapperInterface<UnionTypeNode>
 */
final readonly class UnionPhpDocTypeMapper implements PhpDocTypeMapperInterface
{
    public function __construct(private TypeFactory $typeFactory, private \Flames\Code\Upgrade\StaticTypeMapper\PhpDocParser\IdentifierPhpDocTypeMapper $identifierPhpDocTypeMapper, private \Flames\Code\Upgrade\StaticTypeMapper\PhpDocParser\IntersectionPhpDocTypeMapper $intersectionPhpDocTypeMapper, private TypeNodeResolver $typeNodeResolver)
    {
    }
    public function getNodeType(): string
    {
        return UnionTypeNode::class;
    }
    /**
     * @param UnionTypeNode $typeNode
     */
    public function mapToPHPStanType(TypeNode $typeNode, Node $node, NameScope $nameScope): Type
    {
        $unionedTypes = [];
        foreach ($typeNode->types as $unionedTypeNode) {
            if ($unionedTypeNode instanceof IdentifierTypeNode) {
                $unionedTypes[] = $this->identifierPhpDocTypeMapper->mapToPHPStanType($unionedTypeNode, $node, $nameScope);
                continue;
            }
            if ($unionedTypeNode instanceof IntersectionTypeNode) {
                $unionedTypes[] = $this->intersectionPhpDocTypeMapper->mapToPHPStanType($unionedTypeNode, $node, $nameScope);
                continue;
            }
            $unionedTypes[] = $this->typeNodeResolver->resolve($unionedTypeNode, $nameScope);
        }
        // to prevent missing class error, e.g. in tests
        return $this->typeFactory->createMixedPassedOrUnionTypeAndKeepConstant($unionedTypes);
    }
}
