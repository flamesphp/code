<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
use Flames\Code\Upgrade\PhpAttribute\Enum\DocTagNodeState;
use Flames\Code\Upgrade\Validation\RectorAssert;
use FlamesPrefix202610\Webmozart\Assert\InvalidArgumentException;
/**
 * @implements AnnotationToAttributeMapperInterface<ArrayItemNode>
 */
final class ArrayItemNodeAnnotationToAttributeMapper implements AnnotationToAttributeMapperInterface
{
    private AnnotationToAttributeMapper $annotationToAttributeMapper;
    /**
     * Avoid circular reference
     */
    public function autowire(AnnotationToAttributeMapper $annotationToAttributeMapper): void
    {
        $this->annotationToAttributeMapper = $annotationToAttributeMapper;
    }
    /**
     * @param mixed $value
     */
    public function isCandidate($value): bool
    {
        return $value instanceof ArrayItemNode;
    }
    /**
     * @param ArrayItemNode $arrayItemNode
     */
    public function map($arrayItemNode): ArrayItem
    {
        $valueExpr = $this->annotationToAttributeMapper->map($arrayItemNode->value);
        if ($valueExpr === DocTagNodeState::REMOVE_ARRAY) {
            return new ArrayItem(new String_($valueExpr));
        }
        if ($arrayItemNode->key !== null) {
            /** @var Expr $keyExpr */
            $keyExpr = $this->annotationToAttributeMapper->map($arrayItemNode->key);
        } else {
            if ($this->hasNoParenthesesAnnotation($arrayItemNode)) {
                try {
                    RectorAssert::className(ltrim((string) $arrayItemNode->value, '@'));
                    $identifierTypeNode = new IdentifierTypeNode($arrayItemNode->value);
                    $arrayItemNode->value = new DoctrineAnnotationTagValueNode($identifierTypeNode);
                    return $this->map($arrayItemNode);
                } catch (InvalidArgumentException) {
                }
            }
            $keyExpr = null;
        }
        // @todo how to skip natural integer keys?
        return new ArrayItem($valueExpr, $keyExpr);
    }
    private function hasNoParenthesesAnnotation(ArrayItemNode $arrayItemNode): bool
    {
        if ($arrayItemNode->value instanceof StringNode) {
            return \false;
        }
        if (!is_string($arrayItemNode->value)) {
            return \false;
        }
        if (!str_starts_with($arrayItemNode->value, '@')) {
            return \false;
        }
        return !str_ends_with($arrayItemNode->value, ')');
    }
}
