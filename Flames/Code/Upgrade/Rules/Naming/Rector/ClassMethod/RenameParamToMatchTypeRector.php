<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Rules\Naming\ExpectedNameResolver\MatchParamTypeExpectedNameResolver;
use Flames\Code\Upgrade\Rules\Naming\Guard\BreakingVariableRenameGuard;
use Flames\Code\Upgrade\Rules\Naming\Naming\ExpectedNameResolver;
use Flames\Code\Upgrade\Rules\Naming\ParamRenamer\ParamRenamer;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\ParamRename;
use Flames\Code\Upgrade\Rules\Naming\ValueObjectFactory\ParamRenameFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\Skipper\FileSystem\PathNormalizer;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Naming\Rector\ClassMethod\RenameParamToMatchTypeRectorTest
 */
final class RenameParamToMatchTypeRector extends AbstractRector
{
    public function __construct(private readonly BreakingVariableRenameGuard $breakingVariableRenameGuard, private readonly ExpectedNameResolver $expectedNameResolver, private readonly MatchParamTypeExpectedNameResolver $matchParamTypeExpectedNameResolver, private readonly ParamRenameFactory $paramRenameFactory, private readonly ParamRenamer $paramRenamer, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Rename param to match ClassType', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(Apple $pie)
    {
        $food = $pie;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(Apple $apple)
    {
        $food = $apple;
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
        return [ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }
    /**
     * @param ClassMethod|Function_|Closure|ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->params as $param) {
            // skip as array-like
            if ($param->variadic) {
                continue;
            }
            if ($param->type === null) {
                continue;
            }
            if ($this->skipExactType($param)) {
                return null;
            }
            if ($node instanceof ClassMethod && $this->shouldSkipClassMethodFromVendor($node)) {
                return null;
            }
            $expectedName = $this->expectedNameResolver->resolveForParamIfNotYet($param);
            if ($expectedName === null) {
                continue;
            }
            if ($this->shouldSkipParam($param, $expectedName, $node)) {
                continue;
            }
            $expectedName = $this->matchParamTypeExpectedNameResolver->resolve($param);
            if ($expectedName === null) {
                continue;
            }
            $paramRename = $this->paramRenameFactory->createFromResolvedExpectedName($node, $param, $expectedName);
            if (!$paramRename instanceof ParamRename) {
                continue;
            }
            $this->paramRenamer->rename($paramRename);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * Avoid renaming parameters of a class method, that is located in /vendor,
     * to keep name matching for named arguments.
     */
    private function shouldSkipClassMethodFromVendor(ClassMethod $classMethod): bool
    {
        if ($classMethod->isPrivate()) {
            return \false;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($classMethod);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        $ancestorClassReflections = array_filter($classReflection->getAncestors(), fn(ClassReflection $ancestorClassReflection): bool => $classReflection->getName() !== $ancestorClassReflection->getName());
        $methodName = $this->getName($classMethod);
        foreach ($ancestorClassReflections as $ancestorClassReflection) {
            // internal
            if ($ancestorClassReflection->getFileName() === null) {
                continue;
            }
            if (!$ancestorClassReflection->hasNativeMethod($methodName)) {
                continue;
            }
            $path = PathNormalizer::normalize($ancestorClassReflection->getFileName());
            if (str_contains($path, '/vendor/')) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $classMethod
     */
    private function shouldSkipParam(Param $param, string $expectedName, $classMethod): bool
    {
        /** @var string $paramName */
        $paramName = $this->getName($param);
        if ($this->breakingVariableRenameGuard->shouldSkipParam($paramName, $expectedName, $classMethod, $param)) {
            return \true;
        }
        if (!$classMethod instanceof ClassMethod) {
            return \false;
        }
        // promoted property
        if (!$this->isName($classMethod, MethodName::CONSTRUCT)) {
            return \false;
        }
        return $param->isPromoted();
    }
    /**
     * Skip couple quote vague types, that could be named explicitly on purpose.
     */
    private function skipExactType(Param $param): bool
    {
        if (!$param->type instanceof Node) {
            return \false;
        }
        return $this->isName($param->type, Node::class);
    }
}
