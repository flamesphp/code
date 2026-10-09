<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute;

use PhpParser\BuilderHelpers;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
use Flames\Code\Upgrade\PhpAttribute\Enum\DocTagNodeState;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Tests\PhpAttribute\AnnotationToAttributeMapper\AnnotationToAttributeMapperTest
 */
final readonly class AnnotationToAttributeMapper
{
    /**
     * @param AnnotationToAttributeMapperInterface[] $annotationToAttributeMappers
     */
    public function __construct(private array $annotationToAttributeMappers)
    {
        Assert::notEmpty($this->annotationToAttributeMappers);
    }
    /**
     * @param mixed $value
     * @return mixed
     */
    public function map($value)
    {
        foreach ($this->annotationToAttributeMappers as $annotationToAttributeMapper) {
            if ($annotationToAttributeMapper->isCandidate($value)) {
                return $annotationToAttributeMapper->map($value);
            }
        }
        if ($value instanceof Expr) {
            return $value;
        }
        // remove node, as handled elsewhere
        if ($value instanceof DoctrineAnnotationTagValueNode) {
            return DocTagNodeState::REMOVE_ARRAY;
        }
        if ($value instanceof ArrayItemNode) {
            return BuilderHelpers::normalizeValue((string) $value);
        }
        if ($value instanceof StringNode) {
            return new String_($value->value, [AttributeKey::KIND => $value->getAttribute(AttributeKey::KIND)]);
        }
        // fallback
        return BuilderHelpers::normalizeValue($value);
    }
}
