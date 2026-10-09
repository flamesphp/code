<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation\CurlyListNode;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
use Flames\Code\Upgrade\PhpAttribute\Enum\DocTagNodeState;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @implements AnnotationToAttributeMapperInterface<CurlyListNode>
 */
final class CurlyListNodeAnnotationToAttributeMapper implements AnnotationToAttributeMapperInterface
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
        return $value instanceof CurlyListNode;
    }
    /**
     * @param CurlyListNode $value
     */
    public function map($value): Array_
    {
        $arrayItems = [];
        $arrayItemNodes = $value->getValues();
        $loop = -1;
        foreach ($arrayItemNodes as $arrayItemNode) {
            $valueExpr = $this->annotationToAttributeMapper->map($arrayItemNode);
            // remove node
            if ($valueExpr === DocTagNodeState::REMOVE_ARRAY) {
                continue;
            }
            Assert::isInstanceOf($valueExpr, ArrayItem::class);
            if (!is_numeric($arrayItemNode->key)) {
                $arrayItems[] = $valueExpr;
                continue;
            }
            ++$loop;
            $arrayItemNodeKey = (int) $arrayItemNode->key;
            if ($loop === $arrayItemNodeKey) {
                $arrayItems[] = $valueExpr;
                continue;
            }
            $valueExpr->key = new Int_($arrayItemNodeKey);
            $arrayItems[] = $valueExpr;
        }
        return new Array_($arrayItems);
    }
}
