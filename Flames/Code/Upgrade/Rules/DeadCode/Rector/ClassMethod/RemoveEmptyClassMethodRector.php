<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Comment\Doc;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\DeprecatedTagValueNode;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Configuration\Parameter\FeatureFlags;
use Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer\IsClassMethodUsedAnalyzer;
use Flames\Code\Upgrade\Rules\DeadCode\NodeManipulator\ControllerClassMethodManipulator;
use Flames\Code\Upgrade\NodeAnalyzer\ParamAnalyzer;
use Flames\Code\Upgrade\NodeManipulator\ClassMethodManipulator;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRectorTest
 */
final class RemoveEmptyClassMethodRector extends AbstractRector
{
    public function __construct(private readonly ClassMethodManipulator $classMethodManipulator, private readonly ControllerClassMethodManipulator $controllerClassMethodManipulator, private readonly ParamAnalyzer $paramAnalyzer, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly IsClassMethodUsedAnalyzer $isClassMethodUsedAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove empty class methods not required by parents', [new CodeSample(<<<'CODE_SAMPLE'
class OrphanClass
{
    public function __construct()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class OrphanClass
{
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
        $hasChanged = \false;
        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof ClassMethod) {
                continue;
            }
            if ($stmt->stmts !== null && $stmt->stmts !== []) {
                continue;
            }
            if ($stmt->isAbstract()) {
                continue;
            }
            if ($stmt->isFinal() && !$node->isFinal() && FeatureFlags::treatClassesAsFinal($node) === \false) {
                continue;
            }
            if ($this->shouldSkipNonFinalNonPrivateClassMethod($node, $stmt)) {
                continue;
            }
            if ($this->shouldSkipClassMethod($node, $stmt)) {
                continue;
            }
            unset($node->stmts[$key]);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function shouldSkipNonFinalNonPrivateClassMethod(Class_ $class, ClassMethod $classMethod): bool
    {
        if ($class->isFinal() || FeatureFlags::treatClassesAsFinal($class)) {
            return \false;
        }
        if ($classMethod->isMagic()) {
            return \false;
        }
        if ($classMethod->isProtected()) {
            return \true;
        }
        return $classMethod->isPublic();
    }
    private function shouldSkipClassMethod(Class_ $class, ClassMethod $classMethod): bool
    {
        // is method called somewhere else in the class?
        $scope = ScopeFetcher::fetch($class);
        if ($this->isClassMethodUsedAnalyzer->isClassMethodUsed($class, $classMethod, $scope)) {
            return \true;
        }
        if ($this->classMethodManipulator->isNamedConstructor($classMethod)) {
            return \true;
        }
        // anonymous class extending a parent uses empty constructor on purpose,
        // to avoid parent constructor being invoked
        if ($class->isAnonymous() && $class->extends instanceof Name && $this->isName($classMethod, MethodName::CONSTRUCT)) {
            return \true;
        }
        if ($this->classMethodManipulator->hasParentMethodOrInterfaceMethod($class, $classMethod->name->toString())) {
            return \true;
        }
        if ($this->paramAnalyzer->hasPropertyPromotion($classMethod->params)) {
            return \true;
        }
        if ($this->hasDeprecatedAnnotation($classMethod)) {
            return \true;
        }
        if ($this->controllerClassMethodManipulator->isControllerClassMethod($class, $classMethod)) {
            return \true;
        }
        if ($this->isName($classMethod, MethodName::CLONE)) {
            return !$classMethod->isPublic();
        }
        if ($this->isName($classMethod, MethodName::INVOKE)) {
            return \true;
        }
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        return $this->isAttributeMarkerConstructor($classMethod, $classReflection);
    }
    private function hasDeprecatedAnnotation(ClassMethod $classMethod): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($classMethod);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return \false;
        }
        return $phpDocInfo->hasByType(DeprecatedTagValueNode::class);
    }
    /**
     * Skip constructor in attributes as might be a marker parameter
     */
    private function isAttributeMarkerConstructor(ClassMethod $classMethod, ClassReflection $classReflection): bool
    {
        if (!$this->isName($classMethod, MethodName::CONSTRUCT)) {
            return \false;
        }
        if (!$classReflection->isAttributeClass()) {
            return \false;
        }
        return $classMethod->getDocComment() instanceof Doc;
    }
}
