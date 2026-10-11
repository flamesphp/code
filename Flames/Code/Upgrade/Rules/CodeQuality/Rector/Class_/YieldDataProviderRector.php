<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignRef;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\YieldFrom;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\Type\Generic\GenericObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer\IsClassMethodUsedAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\NodeTransformer;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\PHPUnit\NodeFinder\DataProviderClassMethodFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://medium.com/tech-tajawal/use-memory-gently-with-yield-in-php-7e62e2480b8d
 * @changelog https://3v4l.org/5PJid
 *
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\YieldDataProviderRectorTest
 */
final class YieldDataProviderRector extends AbstractRector
{
    public function __construct(private readonly NodeTransformer $nodeTransformer, private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly DataProviderClassMethodFinder $dataProviderClassMethodFinder, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly IsClassMethodUsedAnalyzer $isClassMethodUsedAnalyzer, private readonly PhpDocTypeChanger $phpDocTypeChanger, private readonly DocBlockUpdater $docBlockUpdater)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turns array return to yield in data providers', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest implements TestCase
{
    public static function provideData()
    {
        return [
            ['some text']
        ];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest implements TestCase
{
    public static function provideData()
    {
        yield ['some text'];
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
    public function refactor(Node $node): ?Class_
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $hasChanged = \false;
        $dataProviderClassMethods = $this->dataProviderClassMethodFinder->find($node);
        foreach ($dataProviderClassMethods as $dataProviderClassMethod) {
            $array = $this->collectReturnArrayNodesFromClassMethod($dataProviderClassMethod);
            if (!$array instanceof Array_) {
                continue;
            }
            $scope = ScopeFetcher::fetch($node);
            if ($this->isClassMethodUsedAnalyzer->isClassMethodUsed($node, $dataProviderClassMethod, $scope)) {
                continue;
            }
            $this->transformArrayToYieldsOnMethodNode($dataProviderClassMethod, $array);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function collectReturnArrayNodesFromClassMethod(ClassMethod $classMethod): ?Array_
    {
        if ($classMethod->stmts === null) {
            return null;
        }
        $yieldedFromExpr = null;
        foreach ($classMethod->stmts as $statement) {
            if ($statement instanceof Expression) {
                $statement = $statement->expr;
            }
            if ($statement instanceof Return_) {
                $returnedExpr = $statement->expr;
                if (!$returnedExpr instanceof Array_) {
                    return null;
                }
                return $returnedExpr;
            }
            if ($statement instanceof YieldFrom) {
                if (!$statement->expr instanceof Array_) {
                    return null;
                }
                if ($yieldedFromExpr instanceof Array_) {
                    return null;
                }
                $yieldedFromExpr = $statement->expr;
            } elseif (!$statement instanceof Assign && !$statement instanceof AssignRef && !$statement instanceof AssignOp) {
                return null;
            }
        }
        return $yieldedFromExpr;
    }
    private function transformArrayToYieldsOnMethodNode(ClassMethod $classMethod, Array_ $array): void
    {
        $yields = $this->nodeTransformer->transformArrayToYields($array);
        $this->removeReturnTag($classMethod);
        // change return typehint
        $classMethod->returnType = new FullyQualified('Iterator');
        $commentReturn = [];
        foreach ((array) $classMethod->stmts as $key => $classMethodStmt) {
            if ($classMethodStmt instanceof Expression) {
                $classMethodStmt = $classMethodStmt->expr;
            }
            if (!$classMethodStmt instanceof Return_ && !$classMethodStmt instanceof YieldFrom) {
                continue;
            }
            $commentReturn = $classMethodStmt->getAttribute(AttributeKey::COMMENTS) ?? [];
            unset($classMethod->stmts[$key]);
        }
        if (isset($yields[0])) {
            $yields[0]->setAttribute(AttributeKey::COMMENTS, array_merge($commentReturn, $yields[0]->getAttribute(AttributeKey::COMMENTS) ?? []));
        }
        $classMethod->stmts = array_merge((array) $classMethod->stmts, $yields);
    }
    private function removeReturnTag(ClassMethod $classMethod): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
        if (!$phpDocInfo->getReturnTagValue() instanceof ReturnTagValueNode) {
            return;
        }
        if ($phpDocInfo->getReturnType()->isArray()->yes()) {
            $keyType = $phpDocInfo->getReturnType()->getIterableKeyType();
            $itemType = $phpDocInfo->getReturnType()->getIterableValueType();
            $this->phpDocTypeChanger->changeReturnType($classMethod, $phpDocInfo, new GenericObjectType('Iterator', [$keyType, $itemType]));
        } else {
            $phpDocInfo->removeByType(ReturnTagValueNode::class);
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($classMethod);
        }
    }
}
