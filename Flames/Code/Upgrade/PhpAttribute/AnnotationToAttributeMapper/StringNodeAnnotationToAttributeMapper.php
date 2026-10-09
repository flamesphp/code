<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
/**
 * @implements AnnotationToAttributeMapperInterface<StringNode>
 */
final class StringNodeAnnotationToAttributeMapper implements AnnotationToAttributeMapperInterface
{
    /**
     * @param mixed $value
     */
    public function isCandidate($value): bool
    {
        return $value instanceof StringNode;
    }
    /**
     * @param StringNode $value
     */
    public function map($value): String_
    {
        return new String_($value->value, [AttributeKey::KIND => $value->getAttribute(AttributeKey::KIND)]);
    }
}
