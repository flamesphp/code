<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony34\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation\CurlyListNode;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\Enum\SensioAttribute;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyAnnotation;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/routing.html#method-annotation
 * @changelog https://stackoverflow.com/questions/51171934/how-to-fix-symfony-3-4-route-and-method-deprecation
 *
 * @see \Flames\Code\Upgrade\Symfony34\Rector\ClassMethod\MergeMethodAnnotationToRouteAnnotationRectorTest
 */
final class MergeMethodAnnotationToRouteAnnotationRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly PhpDocTagRemover $phpDocTagRemover, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/routing', '>=3.4');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Merge removed @Method annotation to @Route one', [new CodeSample(<<<'CODE_SAMPLE'
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/show/{id}")
     * @Method({"GET", "HEAD"})
     */
    public function show($id)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/show/{id}", methods={"GET","HEAD"})
     */
    public function show($id)
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
        return [ClassLike::class];
    }
    /**
     * @param ClassLike $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if (!$classMethod->isPublic()) {
                continue;
            }
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
            $sensioDoctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClass(SensioAttribute::METHOD);
            if (!$sensioDoctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
                continue;
            }
            // get all routes
            $symfonyDoctrineAnnotationTagValueNodes = $phpDocInfo->findByAnnotationClass(SymfonyAnnotation::ROUTE);
            // no symfony route? skip it
            if ($symfonyDoctrineAnnotationTagValueNodes === []) {
                continue;
            }
            $sensioMethodsCurlyListNode = $this->resolveMethodsCurlyListNode($sensioDoctrineAnnotationTagValueNode);
            if (!$sensioMethodsCurlyListNode instanceof CurlyListNode) {
                continue;
            }
            $this->decorateRoutesWithMethods($symfonyDoctrineAnnotationTagValueNodes, $sensioMethodsCurlyListNode);
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $sensioDoctrineAnnotationTagValueNode);
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($classMethod);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function resolveMethodsCurlyListNode(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode): ?\Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation\CurlyListNode
    {
        $methodsParameter = $doctrineAnnotationTagValueNode->getValue('methods');
        if ($methodsParameter instanceof ArrayItemNode && $methodsParameter->value instanceof CurlyListNode) {
            return $methodsParameter->value;
        }
        $arrayItemNode = $doctrineAnnotationTagValueNode->getSilentValue();
        if (!$arrayItemNode instanceof ArrayItemNode) {
            return null;
        }
        if ($arrayItemNode->value instanceof CurlyListNode) {
            return $arrayItemNode->value;
        }
        if ($arrayItemNode->value instanceof StringNode) {
            return new CurlyListNode([new ArrayItemNode($arrayItemNode->value)]);
        }
        return null;
    }
    /**
     * @param DoctrineAnnotationTagValueNode[] $symfonyDoctrineAnnotationTagValueNodes
     */
    private function decorateRoutesWithMethods(array $symfonyDoctrineAnnotationTagValueNodes, CurlyListNode $sensioMethodsCurlyListNode): void
    {
        foreach ($symfonyDoctrineAnnotationTagValueNodes as $symfonyDoctrineAnnotationTagValueNode) {
            $symfonyMethodsArrayItemNode = $symfonyDoctrineAnnotationTagValueNode->getValue('methods');
            // value is already filled, do not enter anything
            if ($symfonyMethodsArrayItemNode instanceof ArrayItemNode) {
                continue;
            }
            $symfonyDoctrineAnnotationTagValueNode->values[] = new ArrayItemNode($sensioMethodsCurlyListNode, 'methods');
        }
    }
}
