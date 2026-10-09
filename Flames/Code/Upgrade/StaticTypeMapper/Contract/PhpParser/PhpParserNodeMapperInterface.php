<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpParser;

use PhpParser\Node;
use PHPStan\Type\Type;
/**
 * @template TNode as \PhpParser\Node
 */
interface PhpParserNodeMapperInterface
{
    /**
     * @return class-string<TNode>
     */
    public function getNodeType(): string;
    /**
     * @param TNode $node
     */
    public function mapToPHPStan(Node $node): Type;
}
