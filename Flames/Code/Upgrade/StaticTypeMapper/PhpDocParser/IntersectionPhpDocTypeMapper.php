<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\PhpDocParser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use PHPStan\Analyser\NameScope;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IntersectionTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpDocParser\PhpDocTypeMapperInterface;
/**
 * @implements PhpDocTypeMapperInterface<IntersectionTypeNode>
 */
final readonly class IntersectionPhpDocTypeMapper implements PhpDocTypeMapperInterface
{
    public function __construct(private \Flames\Code\Upgrade\StaticTypeMapper\PhpDocParser\IdentifierPhpDocTypeMapper $identifierPhpDocTypeMapper)
    {
    }
    public function getNodeType(): string
    {
        return IntersectionTypeNode::class;
    }
    /**
     * @param IntersectionTypeNode $typeNode
     */
    public function mapToPHPStanType(TypeNode $typeNode, Node $node, NameScope $nameScope): Type
    {
        $intersectionedTypes = [];
        foreach ($typeNode->types as $intersectionedTypeNode) {
            if (!$intersectionedTypeNode instanceof IdentifierTypeNode) {
                return new MixedType();
            }
            $intersectionedTypes[] = $this->identifierPhpDocTypeMapper->mapIdentifierTypeNode($intersectionedTypeNode, $node);
        }
        return new IntersectionType($intersectionedTypes);
    }
}
