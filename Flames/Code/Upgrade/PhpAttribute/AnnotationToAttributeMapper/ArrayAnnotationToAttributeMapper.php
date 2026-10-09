<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
use Flames\Code\Upgrade\PhpAttribute\Enum\DocTagNodeState;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @implements AnnotationToAttributeMapperInterface<mixed[]>
 */
final class ArrayAnnotationToAttributeMapper implements AnnotationToAttributeMapperInterface
{
    private AnnotationToAttributeMapper $annotationToAttributeMapper;
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function autowire(AnnotationToAttributeMapper $annotationToAttributeMapper): void
    {
        $this->annotationToAttributeMapper = $annotationToAttributeMapper;
    }
    /**
     * @param mixed $value
     */
    public function isCandidate($value): bool
    {
        return is_array($value);
    }
    /**
     * @param mixed[] $value
     */
    public function map($value): Array_
    {
        $arrayItems = [];
        foreach ($value as $key => $singleValue) {
            $valueExpr = $this->annotationToAttributeMapper->map($singleValue);
            // remove node
            if ($valueExpr === DocTagNodeState::REMOVE_ARRAY) {
                continue;
            }
            // remove value
            if ($this->isRemoveArrayPlaceholder($singleValue)) {
                continue;
            }
            if ($valueExpr instanceof ArrayItem) {
                $valueExpr = $this->resolveValueExprWithSingleQuoteHandling($valueExpr);
                $arrayItems[] = $this->resolveValueExprWithSingleQuoteHandling($valueExpr);
            } else {
                $keyExpr = null;
                if (!is_int($key)) {
                    $keyExpr = $this->annotationToAttributeMapper->map($key);
                    Assert::isInstanceOf($keyExpr, Expr::class);
                }
                $arrayItems[] = new ArrayItem($valueExpr, $keyExpr);
            }
        }
        return new Array_($arrayItems);
    }
    private function resolveValueExprWithSingleQuoteHandling(ArrayItem $arrayItem): ArrayItem
    {
        if (!$arrayItem->key instanceof Expr && $arrayItem->value instanceof ClassConstFetch && $arrayItem->value->class instanceof Name && str_contains((string) $arrayItem->value->class, "'")) {
            $arrayItem->value = new String_($this->valueResolver->getValue($arrayItem->value));
            return $arrayItem;
        }
        return $arrayItem;
    }
    /**
     * @param mixed $value
     */
    private function isRemoveArrayPlaceholder($value): bool
    {
        if (!is_array($value)) {
            return \false;
        }
        return in_array(DocTagNodeState::REMOVE_ARRAY, $value, \true);
    }
}
