<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\Expression;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitor;
use FlamesPrefix202610\PHPUnit\Framework\Assert;
use Flames\Code\Upgrade\Doctrine\TypedCollections\TypeAnalyzer\CollectionTypeDetector;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\Expression\RemoveAssertNotNullOnCollectionRectorTest
 */
final class RemoveAssertNotNullOnCollectionRector extends AbstractRector
{
    public function __construct(private readonly CollectionTypeDetector $collectionTypeDetector)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove ' . Assert::class . '::assertNotNull() on a Collection type', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

class SomeClass
{
    public function run(Collection $collection): void
    {
        Assert::assertNotNull($collection);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

class SomeClass
{
    public function run(Collection $collection): void
    {
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Expression::class];
    }
    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?int
    {
        if (!$node->expr instanceof StaticCall) {
            return null;
        }
        $staticCall = $node->expr;
        if (!$this->isName($staticCall->name, 'assertNotNull')) {
            return null;
        }
        if (!$this->isName($staticCall->class, PHPUnitClassName::ASSERT)) {
            return null;
        }
        $firstArg = $staticCall->getArgs()[0];
        if (!$this->collectionTypeDetector->isCollectionType($firstArg->value)) {
            return null;
        }
        return NodeVisitor::REMOVE_NODE;
    }
}
