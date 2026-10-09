<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\Guard;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
final class StandaloneTypeRemovalGuard
{
    /**
     * @var string[]
     */
    private const array ALLOWED_TYPES = ['false', 'true'];
    public function isLegal(TypeNode $typeNode, Node $node): bool
    {
        if (!$typeNode instanceof IdentifierTypeNode) {
            return \true;
        }
        if (!$node instanceof Identifier) {
            return \true;
        }
        if ($node->toString() !== 'bool') {
            return \true;
        }
        return !in_array($typeNode->name, self::ALLOWED_TYPES, \true);
    }
}
