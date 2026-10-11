<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpDocParser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use PHPStan\Analyser\NameScope;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\Type;
/**
 * @template TTypeNode as TypeNode
 */
interface PhpDocTypeMapperInterface
{
    /**
     * @return class-string<TTypeNode>
     */
    public function getNodeType(): string;
    /**
     * @param TTypeNode $typeNode
     */
    public function mapToPHPStanType(TypeNode $typeNode, Node $node, NameScope $nameScope): Type;
}
