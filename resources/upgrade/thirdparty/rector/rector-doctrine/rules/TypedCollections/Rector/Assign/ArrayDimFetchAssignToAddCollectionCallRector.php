<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\Assign;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\Doctrine\TypedCollections\TypeAnalyzer\CollectionTypeDetector;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\Assign\ArrayDimFetchAssignToAddCollectionCallRectorTest
 */
final class ArrayDimFetchAssignToAddCollectionCallRector extends AbstractRector
{
    public function __construct(private readonly CollectionTypeDetector $collectionTypeDetector)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change add dim assign on collection to an->add() call', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

final class SetFirstParameterArray
{
    /**
     * @var Collection<int, string>
     */
    public $items;

    public function setItems($item)
    {
        $this->items[] = $item;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

final class SetFirstParameterArray
{
    /**
     * @var Collection<int, string>
     */
    public $items;

    public function setItems($item)
    {
        $this->items->add($item);
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Assign::class];
    }
    /**
     * @param Assign $node
     */
    public function refactor(Node $node): ?MethodCall
    {
        if (!$node->var instanceof ArrayDimFetch) {
            return null;
        }
        $arrayDimFetch = $node->var;
        if ($arrayDimFetch->dim instanceof Expr) {
            return null;
        }
        $assignedExpr = $arrayDimFetch->var;
        if (!$this->collectionTypeDetector->isCollectionType($assignedExpr)) {
            return null;
        }
        return new MethodCall($assignedExpr, 'add', [new Arg($node->expr)]);
    }
}
