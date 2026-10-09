<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\ArrayTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\Attributes\AttributeMirrorer;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\BasePhpDocNodeVisitorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor;
final class ArrayTypePhpDocNodeVisitor extends AbstractPhpDocNodeVisitor implements BasePhpDocNodeVisitorInterface
{
    public function __construct(private readonly AttributeMirrorer $attributeMirrorer)
    {
    }
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ArrayTypeNode) {
            return null;
        }
        if ($node instanceof SpacingAwareArrayTypeNode) {
            return null;
        }
        $spacingAwareArrayTypeNode = new SpacingAwareArrayTypeNode($node->type);
        $this->attributeMirrorer->mirror($node, $spacingAwareArrayTypeNode);
        return $spacingAwareArrayTypeNode;
    }
}
