<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeFinder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\MockedMethodTypeResolver;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\SetUpAssignedMockTypesResolver;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\Reflection\MethodParametersAndReturnTypesResolver;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\MockedMethod;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\ParamTypesAndReturnType;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\TypeWillReturnCallableArrowFunctionRectorTest
 */
final class TypeWillReturnCallableArrowFunctionRector extends AbstractRector
{
    private const string WILL_RETURN_CALLBACK = 'willReturnCallback';
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly StaticTypeMapper $staticTypeMapper, private readonly SetUpAssignedMockTypesResolver $setUpAssignedMockTypesResolver, private readonly MethodParametersAndReturnTypesResolver $methodParametersAndReturnTypesResolver, private readonly ReflectionResolver $reflectionResolver, private readonly MockedMethodTypeResolver $mockedMethodTypeResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Decorate callbacks and arrow functions in willReturnCallback() with known param/return types based on reflection method', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function testSomething()
    {
        $this->createMock(SomeClass::class)
            ->method('someMethod')
            ->willReturnCallback(function ($arg) {
                return $arg;
            });
    }
}

final class SomeClass
{
    public function someMethod(string $arg): string
    {
        return $arg . ' !';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function testSomething()
    {
        $this->createMock(SomeClass::class)
            ->method('someMethod')
            ->willReturnCallback(
                function (string $arg): string {
                    return $arg;
                }
            );
    }
}

final class SomeClass
{
    public function someMethod(string $arg): string
    {
        return $arg . ' !';
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
        $currentClassReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$currentClassReflection instanceof ClassReflection) {
            return null;
        }
        $propertyNameToMockedTypes = $this->setUpAssignedMockTypesResolver->resolveFromClass($node);
        $this->traverseNodesWithCallable($node->getMethods(), function (Node $node) use (&$hasChanged, $propertyNameToMockedTypes, $currentClassReflection) {
            if (!$node instanceof MethodCall || $node->isFirstClassCallable()) {
                return null;
            }
            $innerClosure = $this->matchInnerClosure($node);
            if (!$innerClosure instanceof Node) {
                return null;
            }
            if (!$node->var instanceof MethodCall) {
                return null;
            }
            $parentMethodCall = $node->var;
            if (!$this->isName($parentMethodCall->name, 'method')) {
                return null;
            }
            $mockedMethod = $this->mockedMethodTypeResolver->resolve($parentMethodCall, $propertyNameToMockedTypes);
            if (!$mockedMethod instanceof MockedMethod) {
                return null;
            }
            $methodName = $mockedMethod->getMethodName();
            $intersectionType = $mockedMethod->getCallerType();
            $hasChanged = \false;
            $parameterTypesAndReturnType = $this->methodParametersAndReturnTypesResolver->resolveFromReflection($intersectionType, $methodName, $currentClassReflection);
            if (!$parameterTypesAndReturnType instanceof ParamTypesAndReturnType) {
                return null;
            }
            foreach ($innerClosure->params as $key => $param) {
                // avoid typing variadic parameters
                if ($param->variadic) {
                    continue;
                }
                // already filled, lets skip it
                if ($param->type instanceof Node) {
                    continue;
                }
                $nativeParameterType = $parameterTypesAndReturnType->getParamTypes()[$key] ?? null;
                // we need specific non-mixed type
                if ($nativeParameterType === null) {
                    continue;
                }
                if ($nativeParameterType instanceof MixedType) {
                    continue;
                }
                $parameterTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($nativeParameterType, TypeKind::PARAM);
                if (!$parameterTypeNode instanceof Node) {
                    continue;
                }
                $param->type = $parameterTypeNode;
                $hasChanged = \true;
            }
            if (!$innerClosure->returnType instanceof Node) {
                $returnType = $parameterTypesAndReturnType->getReturnType();
                if (!$returnType instanceof Type) {
                    return null;
                }
                if ($this->shouldSkipReturnForConflictWithReturnedNodeType($innerClosure, $returnType)) {
                    return null;
                }
                $returnTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($returnType, TypeKind::RETURN);
                if ($returnTypeNode instanceof Node) {
                    $innerClosure->returnType = $returnTypeNode;
                    $hasChanged = \true;
                }
            }
        });
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure
     */
    public function matchInnerClosure(MethodCall $methodCall)
    {
        if ($this->isName($methodCall->name, 'with')) {
            // special case for nested callback
            $withFirstArg = $methodCall->getArgs()[0];
            if ($withFirstArg->value instanceof MethodCall) {
                $nestedMethodCall = $withFirstArg->value;
                if ($this->isName($nestedMethodCall->name, 'callback')) {
                    $nestedArg = $nestedMethodCall->getArgs()[0];
                    if ($nestedArg->value instanceof ArrowFunction || $nestedArg->value instanceof Closure) {
                        return $nestedArg->value;
                    }
                }
            }
        }
        if ($this->isName($methodCall->name, self::WILL_RETURN_CALLBACK)) {
            $innerArg = $methodCall->getArgs()[0];
            if ($innerArg->value instanceof ArrowFunction || $innerArg->value instanceof Closure) {
                return $innerArg->value;
            }
        }
        return null;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $functionLike
     */
    private function shouldSkipReturnForConflictWithReturnedNodeType($functionLike, Type $returnType): bool
    {
        // find return functionLike, to check current type
        $nodeFinder = new NodeFinder();
        $returns = $nodeFinder->findInstanceOf($functionLike, Return_::class);
        $returnTypes = [];
        foreach ($returns as $return) {
            if ($return->expr instanceof Node) {
                $returnTypes[] = $this->getType($return->expr);
            }
        }
        if (count($returnTypes) === 1) {
            $closureReturnedNodeType = $returnTypes[0];
            if (!$closureReturnedNodeType->isSuperTypeOf($returnType)->yes()) {
                return \true;
            }
        }
        return \false;
    }
}
