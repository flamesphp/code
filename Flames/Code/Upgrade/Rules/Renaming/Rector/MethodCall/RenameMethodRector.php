<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\BuilderHelpers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\NodeManipulator\ClassManipulator;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\Rules\Renaming\Contract\MethodCallRenameInterface;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\MethodCallRename;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\MethodCallRenameWithArrayKey;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Renaming\Rector\MethodCall\RenameMethodRectorTest
 */
final class RenameMethodRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var MethodCallRenameInterface[]
     */
    private array $methodCallRenames = [];
    public function __construct(private readonly ClassManipulator $classManipulator, private readonly ReflectionResolver $reflectionResolver, private readonly ReflectionProvider $reflectionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turn method names to new ones', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
$someObject = new SomeExampleClass;
$someObject->oldMethod();
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$someObject = new SomeExampleClass;
$someObject->newMethod();
CODE_SAMPLE
, [new MethodCallRename('SomeExampleClass', 'oldMethod', 'newMethod')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, NullsafeMethodCall::class, StaticCall::class, Class_::class, Trait_::class, Interface_::class];
    }
    /**
     * @param MethodCall|NullsafeMethodCall|StaticCall|Class_|Interface_|Trait_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Class_ || $node instanceof Trait_ || $node instanceof Interface_) {
            $scope = ScopeFetcher::fetch($node);
            return $this->refactorClass($node, $scope);
        }
        return $this->refactorMethodCallAndStaticCall($node);
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, MethodCallRenameInterface::class);
        $this->methodCallRenames = $configuration;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $call
     */
    private function shouldSkipClassMethod($call, MethodCallRenameInterface $methodCallRename): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflectionSourceObject($call);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        $targetClass = $methodCallRename->getClass();
        if (!$this->reflectionProvider->hasClass($targetClass)) {
            return \false;
        }
        $targetClassReflection = $this->reflectionProvider->getClass($targetClass);
        if ($classReflection->getName() === $targetClassReflection->getName()) {
            return \false;
        }
        // different with configured ClassLike source? it is a child, which may has old and new exists
        if (!$classReflection->hasMethod($methodCallRename->getOldMethod())) {
            return \false;
        }
        return $classReflection->hasMethod($methodCallRename->getNewMethod());
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_ $classOrInterface
     */
    private function hasClassNewClassMethod($classOrInterface, MethodCallRenameInterface $methodCallRename): bool
    {
        return (bool) $classOrInterface->getMethod($methodCallRename->getNewMethod());
    }
    private function shouldKeepForParentInterface(MethodCallRenameInterface $methodCallRename, ?ClassReflection $classReflection): bool
    {
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        // interface can change current method, as parent contract is still valid
        if (!$classReflection->isInterface()) {
            return \false;
        }
        return $this->classManipulator->hasParentMethodOrInterface($methodCallRename->getObjectType(), $methodCallRename->getOldMethod());
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_ $classOrInterface
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_|null
     */
    private function refactorClass($classOrInterface, Scope $scope)
    {
        $classReflection = $scope->getClassReflection();
        $hasChanged = \false;
        foreach ($classOrInterface->getMethods() as $classMethod) {
            $methodName = $this->getName($classMethod->name);
            if ($methodName === null) {
                continue;
            }
            foreach ($this->methodCallRenames as $methodCallRename) {
                if ($this->shouldSkipRename($methodName, $classMethod, $methodCallRename, $classOrInterface, $classReflection)) {
                    continue;
                }
                $classMethod->name = new Identifier($methodCallRename->getNewMethod());
                $hasChanged = \true;
                continue 2;
            }
        }
        if ($hasChanged) {
            return $classOrInterface;
        }
        return null;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_ $classOrInterface
     */
    private function shouldSkipRename(string $methodName, ClassMethod $classMethod, MethodCallRenameInterface $methodCallRename, $classOrInterface, ?ClassReflection $classReflection): bool
    {
        if (!$this->nodeNameResolver->isStringName($methodName, $methodCallRename->getOldMethod())) {
            return \true;
        }
        if (!$classReflection instanceof ClassReflection && $classOrInterface instanceof Trait_) {
            return $this->hasClassNewClassMethod($classOrInterface, $methodCallRename);
        }
        if (!$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($classMethod, $methodCallRename->getObjectType())) {
            return \true;
        }
        if ($this->shouldKeepForParentInterface($methodCallRename, $classReflection)) {
            return \true;
        }
        return $this->hasClassNewClassMethod($classOrInterface, $methodCallRename);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall $call
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch|null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall
     */
    private function refactorMethodCallAndStaticCall($call)
    {
        $callName = $this->getName($call->name);
        if ($callName === null) {
            return null;
        }
        foreach ($this->methodCallRenames as $methodCallRename) {
            if (!$this->nodeNameResolver->isStringName($callName, $methodCallRename->getOldMethod())) {
                continue;
            }
            if (!$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($call, $methodCallRename->getObjectType())) {
                continue;
            }
            if ($this->shouldSkipClassMethod($call, $methodCallRename)) {
                continue;
            }
            $call->name = new Identifier($methodCallRename->getNewMethod());
            if ($methodCallRename instanceof MethodCallRenameWithArrayKey) {
                return new ArrayDimFetch($call, BuilderHelpers::normalizeValue($methodCallRename->getArrayKey()));
            }
            return $call;
        }
        return null;
    }
}
