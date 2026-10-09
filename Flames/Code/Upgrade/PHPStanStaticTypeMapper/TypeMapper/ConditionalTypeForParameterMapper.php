<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\PHPStanStaticTypeMapper;
/**
 * @implements TypeMapperInterface<ConditionalTypeForParameter>
 */
final class ConditionalTypeForParameterMapper implements TypeMapperInterface
{
    private PHPStanStaticTypeMapper $phpStanStaticTypeMapper;
    public function autowire(PHPStanStaticTypeMapper $phpStanStaticTypeMapper): void
    {
        $this->phpStanStaticTypeMapper = $phpStanStaticTypeMapper;
    }
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [ConditionalTypeForParameter::class];
    }
    /**
     * @param ConditionalTypeForParameter $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return $type->toPhpDocNode();
    }
    /**
     * @param ConditionalTypeForParameter $type
     * @param TypeKind::* $typeKind
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        $type = TypeCombinator::union($type->getIf(), $type->getElse());
        return $this->phpStanStaticTypeMapper->mapToPhpParserNode($type, $typeKind);
    }
}
