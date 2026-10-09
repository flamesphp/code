<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\Mapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Exception\NotImplementedYetException;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpParser\PhpParserNodeMapperInterface;
final readonly class PhpParserNodeMapper
{
    /**
     * @param PhpParserNodeMapperInterface[] $phpParserNodeMappers
     */
    public function __construct(private array $phpParserNodeMappers)
    {
    }
    public function mapToPHPStanType(Node $node): Type
    {
        $matchedNodeMapper = null;
        $matchedNodeType = null;
        foreach ($this->phpParserNodeMappers as $phpParserNodeMapper) {
            $nodeType = $phpParserNodeMapper->getNodeType();
            if (!$node instanceof $nodeType) {
                continue;
            }
            // pick the most specific mapper: a mapper for a child node wins over one for its
            // parent node, regardless of registration order
            if ($matchedNodeType === null || is_a($nodeType, $matchedNodeType, \true)) {
                $matchedNodeType = $nodeType;
                $matchedNodeMapper = $phpParserNodeMapper;
            }
        }
        if (!$matchedNodeMapper instanceof PhpParserNodeMapperInterface) {
            throw new NotImplementedYetException($node::class);
        }
        return $matchedNodeMapper->mapToPHPStan($node);
    }
}
