<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\New_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Doctrine\TypedCollections\TypeAnalyzer\CollectionTypeDetector;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\New_\RemoveNewArrayCollectionWrapRectorTest
 */
final class RemoveNewArrayCollectionWrapRector extends AbstractRector
{
    public function __construct(private readonly CollectionTypeDetector $collectionTypeDetector)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove new ArrayCollection wrap on collection typed property, as it is always assigned in the constructor', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class SomeClass
{
    public ?Collection $items;

    public function someMethod()
    {
        $values = new ArrayCollection($this->items);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

final class SomeClass
{
    public ?Collection $items;

    public function someMethod()
    {
        $values = $this->items;
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [New_::class];
    }
    /**
     * @param New_ $node
     */
    public function refactor(Node $node): ?Expr
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!$this->isName($node->class, DoctrineClass::ARRAY_COLLECTION)) {
            return null;
        }
        if ($node->getArgs() === []) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        if (!$this->collectionTypeDetector->isCollectionType($firstArg->value)) {
            return null;
        }
        return $firstArg->value;
    }
}
