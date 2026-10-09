<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;

use PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
/**
 * @implements AnnotationToAttributeMapperInterface<string>
 */
final class StringAnnotationToAttributeMapper implements AnnotationToAttributeMapperInterface
{
    /**
     * @param mixed $value
     */
    public function isCandidate($value): bool
    {
        if (!is_string($value)) {
            return \false;
        }
        // an unquoted "Class::CONST" reference is handled by the class const fetch mapper;
        // excluding it here keeps the two mappers mutually exclusive, so match order no longer matters
        return !str_contains($value, '::') || str_starts_with($value, '"');
    }
    /**
     * @param string $value
     */
    public function map($value): String_
    {
        if (str_contains($value, "'") && !str_contains($value, "\n")) {
            $kind = String_::KIND_DOUBLE_QUOTED;
        } else {
            $kind = String_::KIND_SINGLE_QUOTED;
        }
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = trim($value, '"');
        }
        return new String_($value, [AttributeKey::KIND => $kind]);
    }
}
