<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpParser\PhpParserNodeMapperInterface;
use Flames\Code\Upgrade\StaticTypeMapper\Mapper\ScalarStringToTypeMapper;
/**
 * @implements PhpParserNodeMapperInterface<Identifier>
 */
final readonly class IdentifierNodeMapper implements PhpParserNodeMapperInterface
{
    public function __construct(private ScalarStringToTypeMapper $scalarStringToTypeMapper)
    {
    }
    public function getNodeType(): string
    {
        return Identifier::class;
    }
    /**
     * @param Identifier $node
     */
    public function mapToPHPStan(Node $node): Type
    {
        return $this->scalarStringToTypeMapper->mapScalarStringToType($node->name);
    }
}
