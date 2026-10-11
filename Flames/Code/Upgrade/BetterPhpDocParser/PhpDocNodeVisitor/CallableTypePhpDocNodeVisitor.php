<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\CallableTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\Attributes\AttributeMirrorer;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\BasePhpDocNodeVisitorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\SpacingAwareCallableTypeNode;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor;
final class CallableTypePhpDocNodeVisitor extends AbstractPhpDocNodeVisitor implements BasePhpDocNodeVisitorInterface
{
    public function __construct(private readonly AttributeMirrorer $attributeMirrorer)
    {
    }
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof CallableTypeNode) {
            return null;
        }
        if ($node instanceof SpacingAwareCallableTypeNode) {
            return null;
        }
        $spacingAwareCallableTypeNode = new SpacingAwareCallableTypeNode($node->identifier, $node->parameters, $node->returnType, []);
        $this->attributeMirrorer->mirror($node, $spacingAwareCallableTypeNode);
        return $spacingAwareCallableTypeNode;
    }
}
