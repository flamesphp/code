<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Doctrine\TypedCollections\NodeAnalyzer\EntityLikeClassDetector;
use Flames\Code\Upgrade\Doctrine\TypedCollections\NodeAnalyzer\InitializedArrayCollectionPropertyResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\Class_\RemoveNullFromInstantiatedArrayCollectionPropertyRectorTest
 */
final class RemoveNullFromInstantiatedArrayCollectionPropertyRector extends AbstractRector
{
    public function __construct(private readonly EntityLikeClassDetector $entityLikeClassDetector, private readonly InitializedArrayCollectionPropertyResolver $initializedArrayCollectionPropertyResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove nullability from instantiated ArrayCollection properties, set it to Collection', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

class SomeClass
{
    private ?Collection $trainings = null;

    public function __construct()
    {
        $this->trainings = new ArrayCollection();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

class SomeClass
{
    private Collection $trainings;

    public function __construct()
    {
        $this->trainings = new ArrayCollection();
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->entityLikeClassDetector->detect($node)) {
            return null;
        }
        $propertyNames = $this->initializedArrayCollectionPropertyResolver->resolve($node);
        if ($propertyNames === []) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            if (!$this->isNames($property, $propertyNames)) {
                continue;
            }
            if ($property->props[0]->default instanceof Expr) {
                $property->props[0]->default = null;
                $hasChanged = \true;
            }
            // has already correct type
            if ($property->type instanceof Name && $this->isName($property->type, DoctrineClass::COLLECTION)) {
                continue;
            }
            $property->type = new FullyQualified(DoctrineClass::COLLECTION);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
