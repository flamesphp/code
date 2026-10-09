<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Exception\NotImplementedYetException;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class PHPStanStaticTypeMapper
{
    /**
     * @param TypeMapperInterface[] $typeMappers
     */
    public function __construct(private array $typeMappers)
    {
        Assert::notEmpty($this->typeMappers);
    }
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        $typeMapper = $this->matchTypeMapper($type);
        if (!$typeMapper instanceof TypeMapperInterface) {
            throw new NotImplementedYetException(__METHOD__ . ' for ' . $type::class);
        }
        return $typeMapper->mapToPHPStanPhpDocTypeNode($type);
    }
    /**
     * @param TypeKind::* $typeKind
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier|null
     */
    public function mapToPhpParserNode(Type $type, string $typeKind)
    {
        $typeMapper = $this->matchTypeMapper($type);
        if (!$typeMapper instanceof TypeMapperInterface) {
            throw new NotImplementedYetException(__METHOD__ . ' for ' . $type::class);
        }
        return $typeMapper->mapToPhpParserNode($type, $typeKind);
    }
    /**
     * Match the most specific mapper: when a type is handled by both a mapper for a parent
     * class and one for its subclass, the subclass mapper wins, regardless of registration order.
     *
     * @return TypeMapperInterface<Type>|null
     */
    private function matchTypeMapper(Type $type): ?TypeMapperInterface
    {
        $matchedTypeMapper = null;
        $matchedNodeClass = null;
        foreach ($this->typeMappers as $typeMapper) {
            foreach ($typeMapper->getNodeClasses() as $nodeClass) {
                if (!$type instanceof $nodeClass) {
                    continue;
                }
                if ($matchedNodeClass === null || is_a($nodeClass, $matchedNodeClass, \true)) {
                    $matchedNodeClass = $nodeClass;
                    $matchedTypeMapper = $typeMapper;
                }
            }
        }
        return $matchedTypeMapper;
    }
}
