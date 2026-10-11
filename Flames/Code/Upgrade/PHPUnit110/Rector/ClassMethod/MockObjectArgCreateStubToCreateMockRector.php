<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit110\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\PHPUnit110\Rector\ClassMethod\MockObjectArgCreateStubToCreateMockRectorTest
 */
final class MockObjectArgCreateStubToCreateMockRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly BetterNodeFinder $betterNodeFinder, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change createStub() to createMock(), when the created variable is passed to a method that requires MockObject type', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $someMock = $this->createStub(SomeClass::class);
        $this->prepareMock($someMock);
    }

    private function prepareMock(MockObject $someMock): void
    {
        $someMock->expects($this->once())
            ->method('someMethod');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $someMock = $this->createMock(SomeClass::class);
        $this->prepareMock($someMock);
    }

    private function prepareMock(MockObject $someMock): void
    {
        $someMock->expects($this->once())
            ->method('someMethod');
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?ClassMethod
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $createStubMethodCallsByVariableName = $this->collectCreateStubAssigns($node);
        if ($createStubMethodCallsByVariableName === []) {
            return null;
        }
        $hasChanged = \false;
        foreach ($createStubMethodCallsByVariableName as $variableName => $createStubMethodCall) {
            if (!$this->isPassedAsMockObjectArg($node, (string) $variableName)) {
                continue;
            }
            $createStubMethodCall->name = new Identifier('createMock');
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @return array<string, MethodCall>
     */
    private function collectCreateStubAssigns(ClassMethod $classMethod): array
    {
        $createStubMethodCallsByVariableName = [];
        foreach ((array) $classMethod->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }
            $assign = $stmt->expr;
            if (!$assign->var instanceof Variable) {
                continue;
            }
            if (!$assign->expr instanceof MethodCall) {
                continue;
            }
            $methodCall = $assign->expr;
            if (!$this->isName($methodCall->name, 'createStub')) {
                continue;
            }
            $variableName = $this->getName($assign->var);
            if ($variableName === null) {
                continue;
            }
            $createStubMethodCallsByVariableName[$variableName] = $methodCall;
        }
        return $createStubMethodCallsByVariableName;
    }
    private function isPassedAsMockObjectArg(ClassMethod $classMethod, string $variableName): bool
    {
        /** @var array<MethodCall|StaticCall> $callLikes */
        $callLikes = $this->betterNodeFinder->findInstancesOfScoped((array) $classMethod->stmts, [MethodCall::class, StaticCall::class]);
        foreach ($callLikes as $callLike) {
            if ($callLike->isFirstClassCallable()) {
                continue;
            }
            foreach ($callLike->getArgs() as $argIndex => $arg) {
                if (!$arg->value instanceof Variable) {
                    continue;
                }
                if (!$this->isName($arg->value, $variableName)) {
                    continue;
                }
                if ($this->isMockObjectParam($callLike, $arg->name, $argIndex)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $callLike
     */
    private function isMockObjectParam($callLike, ?Identifier $identifier, int $argIndex): bool
    {
        $methodReflection = $callLike instanceof MethodCall ? $this->reflectionResolver->resolveMethodReflectionFromMethodCall($callLike) : $this->reflectionResolver->resolveMethodReflectionFromStaticCall($callLike);
        if (!$methodReflection instanceof MethodReflection) {
            return \false;
        }
        $mockObjectType = new ObjectType(PHPUnitClassName::MOCK_OBJECT);
        foreach ($methodReflection->getVariants() as $parametersAcceptor) {
            $parameters = $parametersAcceptor->getParameters();
            if ($identifier instanceof Identifier) {
                foreach ($parameters as $parameter) {
                    if ($parameter->getName() !== $identifier->toString()) {
                        continue;
                    }
                    if ($mockObjectType->isSuperTypeOf($parameter->getType())->yes()) {
                        return \true;
                    }
                }
                continue;
            }
            if (!isset($parameters[$argIndex])) {
                continue;
            }
            if ($mockObjectType->isSuperTypeOf($parameters[$argIndex]->getType())->yes()) {
                return \true;
            }
        }
        return \false;
    }
}
