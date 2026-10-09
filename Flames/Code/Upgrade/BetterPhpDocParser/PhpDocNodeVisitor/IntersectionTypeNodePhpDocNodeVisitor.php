<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor;

use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\Attributes\AttributeMirrorer;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\BasePhpDocNodeVisitorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareIntersectionTypeNode;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor;
final class IntersectionTypeNodePhpDocNodeVisitor extends AbstractPhpDocNodeVisitor implements BasePhpDocNodeVisitorInterface
{
    public function __construct(private readonly AttributeMirrorer $attributeMirrorer)
    {
    }
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof IntersectionTypeNode) {
            return null;
        }
        if ($node instanceof BracketsAwareIntersectionTypeNode) {
            return null;
        }
        $bracketsAwareIntersectionTypeNode = new BracketsAwareIntersectionTypeNode($node->types);
        $this->attributeMirrorer->mirror($node, $bracketsAwareIntersectionTypeNode);
        return $bracketsAwareIntersectionTypeNode;
    }
}
