<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Class_\RemoveRefactorDuplicatedNodeInstanceCheckRectorTest
 */
final class RemoveRefactorDuplicatedNodeInstanceCheckRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove refactor() method of Upgrade rule double check of $classMethod instance, if already defined in @param type', [new CodeSample(<<<'CODE_SAMPLE'
final class RemoveRefactorDuplicatedNodeInstanceCheckRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node)
    {
        if (! $node instanceof ClassMethod) {
            return null;
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class RemoveRefactorDuplicatedNodeInstanceCheckRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node)
    {
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $scope = ScopeFetcher::fetch($node);
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        if (!$classReflection->is(\Flames\Code\Upgrade\Rector\AbstractRector::class)) {
            return null;
        }
        $refactorClassMethod = $node->getMethod('refactor');
        if (!$refactorClassMethod instanceof ClassMethod) {
            return null;
        }
        $firstStmt = $refactorClassMethod->stmts[0] ?? null;
        if (!$firstStmt instanceof If_) {
            return null;
        }
        $instanceofNodeClass = $this->matchBooleanNotInstanceOfNodeClass($firstStmt->cond);
        if (!is_string($instanceofNodeClass)) {
            return null;
        }
        $nodeParamTypeClass = $this->matchNodeParamType($refactorClassMethod);
        $getNodeTypesClassMethod = $node->getMethod('getNodeTypes');
        if (!$getNodeTypesClassMethod instanceof ClassMethod) {
            return null;
        }
        $soleReturn = $getNodeTypesClassMethod->stmts[0] ?? null;
        $nodeTypeClass = null;
        if ($soleReturn instanceof Return_) {
            Assert::isInstanceOf($soleReturn->expr, Expr::class);
            $nodeTypes = $this->valueResolver->getValue($soleReturn->expr);
            if (count($nodeTypes) === 1) {
                $nodeTypeClass = $nodeTypes[0];
            }
        }
        if ($nodeParamTypeClass !== null) {
            if ($nodeParamTypeClass !== $instanceofNodeClass) {
                return null;
            }
        } elseif ($nodeTypeClass !== null) {
            if ($nodeTypeClass !== $instanceofNodeClass) {
                return null;
            }
        } else {
            return null;
        }
        unset($refactorClassMethod->stmts[0]);
        return $node;
    }
    private function matchBooleanNotInstanceOfNodeClass(Expr $expr): ?string
    {
        if (!$expr instanceof BooleanNot) {
            return null;
        }
        $booleanNot = $expr;
        if (!$booleanNot->expr instanceof Instanceof_) {
            return null;
        }
        return $this->getInstanceofNodeClass($booleanNot->expr);
    }
    /**
     * @return class-string<Node>|null
     */
    private function getInstanceofNodeClass(Instanceof_ $instanceof): ?string
    {
        $checkedClassType = $this->getType($instanceof->class);
        if (!$checkedClassType instanceof ObjectType) {
            return null;
        }
        /** @var ClassReflection $classReflection */
        $classReflection = $checkedClassType->getClassReflection();
        if (!$classReflection->is(Node::class)) {
            return null;
        }
        return $classReflection->getName();
    }
    private function matchNodeParamType(ClassMethod $classMethod): ?string
    {
        $classMethodPhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
        $paramType = $classMethodPhpDocInfo->getParamType('$node');
        if (!$paramType instanceof ObjectType) {
            return null;
        }
        if ($paramType instanceof ShortenedObjectType) {
            return $paramType->getFullyQualifiedName();
        }
        return $paramType->getClassName();
    }
}
