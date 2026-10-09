<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony34\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Configuration\RenamedClassesDataCollector;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\Enum\SensioAnnotation;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyAnnotation;
use Flames\Code\Upgrade\Symfony\PhpDocNode\SymfonyRouteTagValueNodeFactory;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://medium.com/@nebkam/symfony-deprecated-route-and-method-annotations-4d5e1d34556a
 * @changelog https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/routing.html#method-annotation
 *
 * @see \Flames\Code\Upgrade\Symfony34\Rector\ClassMethod\ReplaceSensioRouteAnnotationWithSymfonyRectorTest
 */
final class ReplaceSensioRouteAnnotationWithSymfonyRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly SymfonyRouteTagValueNodeFactory $symfonyRouteTagValueNodeFactory, private readonly PhpDocTagRemover $phpDocTagRemover, private readonly RenamedClassesDataCollector $renamedClassesDataCollector, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/routing', '>=3.4');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace Sensio @Route annotation with Symfony one', [new CodeSample(<<<'CODE_SAMPLE'
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

final class SomeClass
{
    /**
     * @Route()
     */
    public function run()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Routing\Annotation\Route;

final class SomeClass
{
    /**
     * @Route()
     */
    public function run()
    {
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
        return [ClassMethod::class, Class_::class];
    }
    /**
     * @param ClassMethod|Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // early return in case of non public method
        if ($node instanceof ClassMethod && !$node->isPublic()) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $sensioDoctrineAnnotationTagValueNodes = $phpDocInfo->findByAnnotationClass(SensioAnnotation::ROUTE);
        // nothing to find
        if ($sensioDoctrineAnnotationTagValueNodes === []) {
            return null;
        }
        foreach ($sensioDoctrineAnnotationTagValueNodes as $sensioDoctrineAnnotationTagValueNode) {
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $sensioDoctrineAnnotationTagValueNode);
            // unset service, that is deprecated
            $sensioDoctrineAnnotationTagValueNode->removeValue('service');
            $values = $sensioDoctrineAnnotationTagValueNode->getValues();
            $symfonyRouteTagValueNode = $this->symfonyRouteTagValueNodeFactory->createFromItems($values);
            // avoid adding this one
            if ($node instanceof Class_ && $this->isEmptySensioRoute($values)) {
                continue;
            }
            $phpDocInfo->addTagValueNode($symfonyRouteTagValueNode);
        }
        $this->renamedClassesDataCollector->addOldToNewClasses([SensioAnnotation::ROUTE => SymfonyAnnotation::ROUTE]);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    /**
     * @param ArrayItemNode[] $values
     */
    private function isEmptySensioRoute(array $values): bool
    {
        if ($values === []) {
            return \true;
        }
        if (count($values) !== 1) {
            return \false;
        }
        $singleValue = $values[0];
        if (!$singleValue instanceof ArrayItemNode) {
            return \false;
        }
        if ($singleValue->key !== null) {
            return \false;
        }
        $stringNode = $singleValue->value;
        if (!$stringNode instanceof StringNode) {
            return \false;
        }
        return $stringNode->value === '/';
    }
}
