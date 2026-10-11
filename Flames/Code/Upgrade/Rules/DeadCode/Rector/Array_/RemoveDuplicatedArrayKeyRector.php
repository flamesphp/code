<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Array_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PreDec;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PreInc;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\PhpParser\Printer\BetterStandardPrinter;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Array_\RemoveDuplicatedArrayKeyRectorTest
 */
final class RemoveDuplicatedArrayKeyRector extends AbstractRector
{
    public function __construct(private readonly BetterStandardPrinter $betterStandardPrinter, private readonly ExprAnalyzer $exprAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove duplicated key in defined arrays', [new CodeSample(<<<'CODE_SAMPLE'
$item = [
    1 => 'A',
    1 => 'B'
];
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$item = [
    1 => 'B'
];
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Array_::class];
    }
    /**
     * @param Array_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $duplicatedKeysArrayItems = $this->resolveDuplicateKeysArrayItems($node);
        if ($duplicatedKeysArrayItems === []) {
            return null;
        }
        foreach ($node->items as $key => $arrayItem) {
            if (!$arrayItem instanceof ArrayItem) {
                continue;
            }
            if (!$this->isArrayItemDuplicated($duplicatedKeysArrayItems, $arrayItem)) {
                continue;
            }
            unset($node->items[$key]);
        }
        return $node;
    }
    /**
     * @return ArrayItem[]
     */
    private function resolveDuplicateKeysArrayItems(Array_ $array): array
    {
        $arrayItemsByKeys = [];
        foreach ($array->items as $arrayItem) {
            if (!$arrayItem instanceof ArrayItem) {
                continue;
            }
            if (!$arrayItem->key instanceof Expr) {
                continue;
            }
            // local variable is mostly fine, other dynamic, just skip
            if (!$arrayItem->key instanceof Variable && $this->exprAnalyzer->isDynamicExpr($arrayItem->key)) {
                continue;
            }
            $keyValue = $this->betterStandardPrinter->print($arrayItem->key);
            $arrayItemsByKeys[$keyValue][] = $arrayItem;
        }
        return $this->filterItemsWithSameKey($arrayItemsByKeys);
    }
    /**
     * @param array<mixed, ArrayItem[]> $arrayItemsByKeys
     * @return array<ArrayItem>
     */
    private function filterItemsWithSameKey(array $arrayItemsByKeys): array
    {
        $duplicatedArrayItems = [];
        foreach ($arrayItemsByKeys as $arrayItems) {
            if (count($arrayItems) <= 1) {
                continue;
            }
            $currentArrayItem = current($arrayItems);
            /** @var Expr $currentArrayItemKey */
            $currentArrayItemKey = $currentArrayItem->key;
            if ($currentArrayItemKey instanceof PreInc) {
                continue;
            }
            if ($currentArrayItemKey instanceof PreDec) {
                continue;
            }
            // keep last one
            array_pop($arrayItems);
            $duplicatedArrayItems = array_merge($duplicatedArrayItems, $arrayItems);
        }
        return $duplicatedArrayItems;
    }
    /**
     * @param ArrayItem[] $duplicatedKeysArrayItems
     */
    private function isArrayItemDuplicated(array $duplicatedKeysArrayItems, ArrayItem $arrayItem): bool
    {
        return in_array($arrayItem, $duplicatedKeysArrayItems, \true);
    }
}
