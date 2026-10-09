<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\Attributes;

use PHPStan\PhpDocParser\Ast\Node;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
final class AttributeMirrorer
{
    /**
     * @var string[]
     */
    private const array ATTRIBUTES_TO_MIRROR = [PhpDocAttributeKey::PARENT, PhpDocAttributeKey::START_AND_END, PhpDocAttributeKey::ORIG_NODE];
    public function mirror(Node $oldNode, Node $newNode): void
    {
        foreach (self::ATTRIBUTES_TO_MIRROR as $attributeToMirror) {
            if (!$oldNode->hasAttribute($attributeToMirror)) {
                continue;
            }
            $attributeValue = $oldNode->getAttribute($attributeToMirror);
            $newNode->setAttribute($attributeToMirror, $attributeValue);
        }
    }
}
