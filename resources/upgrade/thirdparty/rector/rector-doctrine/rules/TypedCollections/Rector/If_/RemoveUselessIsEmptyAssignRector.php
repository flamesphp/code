<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\If_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeVisitor;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Doctrine\TypedCollections\TypeAnalyzer\CollectionTypeDetector;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\If_\RemoveUselessIsEmptyAssignRectorTest
 */
final class RemoveUselessIsEmptyAssignRector extends AbstractRector
{
    public function __construct(private readonly CollectionTypeDetector $collectionTypeDetector)
    {
    }
    public function getNodeTypes(): array
    {
        return [If_::class];
    }
    /**
     * @param If_ $node
     */
    public function refactor(Node $node): ?int
    {
        if (!$node->cond instanceof MethodCall) {
            return null;
        }
        $methodCall = $node->cond;
        if (!$this->isName($methodCall->name, 'isEmpty')) {
            return null;
        }
        if (!$this->collectionTypeDetector->isCollectionType($methodCall->var)) {
            return null;
        }
        if (count($node->stmts) !== 1) {
            return null;
        }
        $soleStmts = $node->stmts[0];
        if (!$soleStmts instanceof Expression) {
            return null;
        }
        if (!$soleStmts->expr instanceof Assign) {
            return null;
        }
        $assign = $soleStmts->expr;
        if (!$this->nodeNameResolver->areNamesEqual($assign->var, $methodCall->var)) {
            return null;
        }
        if (!$this->isArrayCollectionNewInstance($assign->expr)) {
            return null;
        }
        return NodeVisitor::REMOVE_NODE;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove useless isEmpty() check on collection with following new ArrayCollection() instance', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

final class SomeClass
{
    private Collection $collection;

    public function someMethod()
    {
        if ($this->collection->isEmpty()) {
            $this->collection = new ArrayCollection();
        }

        return $this->collection;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

final class SomeClass
{
    private Collection $collection;

    public function someMethod()
    {
        return $this->collection;
    }
}
CODE_SAMPLE
)]);
    }
    private function isArrayCollectionNewInstance(Expr $expr): bool
    {
        if (!$expr instanceof New_) {
            return \true;
        }
        return $this->isName($expr->class, DoctrineClass::ARRAY_COLLECTION);
    }
}
