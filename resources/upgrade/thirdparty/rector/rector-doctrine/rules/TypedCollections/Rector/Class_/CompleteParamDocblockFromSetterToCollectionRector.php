<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Type\Generic\GenericObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\Doctrine\CodeQuality\SetterCollectionResolver;
use Flames\Code\Upgrade\Doctrine\TypedCollections\NodeAnalyzer\EntityLikeClassDetector;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\Class_\CompleteParamDocblockFromSetterToCollectionRectorTest
 */
final class CompleteParamDocblockFromSetterToCollectionRector extends AbstractRector
{
    public function __construct(private readonly PhpDocTypeChanger $phpDocTypeChanger, private readonly EntityLikeClassDetector $entityLikeClassDetector, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly SetterCollectionResolver $setterCollectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Improve @param Doctrine collection types to make them useful both for PHPStan and PHPStorm', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class SomeClass
{
    /**
     * @ORM\OneToMany(targetEntity=Trainer::class, mappedBy="trainer")
     * @var Collection|Trainer[]
     */
    private $trainings = [];

    public function setTrainings($trainings)
    {
        $this->trainings = $trainings;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class SomeClass
{
    /**
     * @ORM\OneToMany(targetEntity=Trainer::class, mappedBy="trainer")
     * @var Collection|Trainer[]
     */
    private $trainings = [];

    /**
     * @param Collection<int, Trainer> $trainings
     */
    public function setTrainings($trainings)
    {
        $this->trainings = $trainings;
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
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            $collectionObjectType = $this->setterCollectionResolver->resolveAssignedGenericCollectionType($node, $classMethod);
            if (!$collectionObjectType instanceof GenericObjectType) {
                continue;
            }
            if (count($classMethod->params) !== 1) {
                continue;
            }
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
            $param = $classMethod->params[0];
            if ($param->type instanceof Node) {
                continue;
            }
            $parameterName = $this->getName($param);
            $hasChangedParamType = $this->phpDocTypeChanger->changeParamType($classMethod, $phpDocInfo, $collectionObjectType, $param, $parameterName);
            if ($hasChangedParamType) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
