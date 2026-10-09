<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\TemplateTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ThrowsTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Exception\NotImplementedYetException;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\PHPStanStaticTypeMapper;
use Flames\Code\Upgrade\StaticTypeMapper\Mapper\PhpParserNodeMapper;
use Flames\Code\Upgrade\StaticTypeMapper\Naming\NameScopeFactory;
use Flames\Code\Upgrade\StaticTypeMapper\PhpDoc\PhpDocTypeMapper;
/**
 * Maps PhpParser <=> PHPStan <=> PHPStan doc <=> string type nodes between all possible formats
 * @see \Flames\Code\Upgrade\Tests\NodeTypeResolver\StaticTypeMapper\StaticTypeMapperTest
 */
final readonly class StaticTypeMapper
{
    public function __construct(private NameScopeFactory $nameScopeFactory, private PHPStanStaticTypeMapper $phpStanStaticTypeMapper, private PhpDocTypeMapper $phpDocTypeMapper, private PhpParserNodeMapper $phpParserNodeMapper)
    {
    }
    public function mapPHPStanTypeToPHPStanPhpDocTypeNode(Type $phpStanType): TypeNode
    {
        return $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($phpStanType);
    }
    /**
     * @param TypeKind::* $typeKind
     * @return Name|ComplexType|Identifier|null
     */
    public function mapPHPStanTypeToPhpParserNode(Type $phpStanType, string $typeKind): ?Node
    {
        return $this->phpStanStaticTypeMapper->mapToPhpParserNode($phpStanType, $typeKind);
    }
    public function mapPhpParserNodePHPStanType(Node $node): Type
    {
        return $this->phpParserNodeMapper->mapToPHPStanType($node);
    }
    public function mapPHPStanPhpDocTypeToPHPStanType(PhpDocTagValueNode $phpDocTagValueNode, Node $node): Type
    {
        if ($phpDocTagValueNode instanceof TemplateTagValueNode) {
            // special case
            if (!$phpDocTagValueNode->bound instanceof TypeNode) {
                return new MixedType();
            }
            $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($node);
            return $this->phpDocTypeMapper->mapToPHPStanType($phpDocTagValueNode->bound, $node, $nameScope);
        }
        if ($phpDocTagValueNode instanceof ReturnTagValueNode || $phpDocTagValueNode instanceof ParamTagValueNode || $phpDocTagValueNode instanceof VarTagValueNode || $phpDocTagValueNode instanceof ThrowsTagValueNode) {
            return $this->mapPHPStanPhpDocTypeNodeToPHPStanType($phpDocTagValueNode->type, $node);
        }
        throw new NotImplementedYetException(__METHOD__ . ' for ' . $phpDocTagValueNode::class);
    }
    public function mapPHPStanPhpDocTypeNodeToPHPStanType(TypeNode $typeNode, Node $node): Type
    {
        $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($node);
        return $this->phpDocTypeMapper->mapToPHPStanType($typeNode, $node, $nameScope);
    }
}
