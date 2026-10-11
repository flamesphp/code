<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Float_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\Reflection\MethodParametersAndReturnTypesResolver;
use Flames\Code\Upgrade\PHPUnit\Enum\BehatClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\ScalarArgumentToExpectedParamTypeRectorTest
 */
final class ScalarArgumentToExpectedParamTypeRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly MethodParametersAndReturnTypesResolver $methodParametersAndReturnTypesResolver, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Correct expected type in setter of tests, if param type is strictly defined', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeTest extends TestCase
{
    public function test()
    {
        $someClass = new SomeClass();
        $someClass->setPhone(12345);
    }
}

final class SomeClass
{
    public function setPhone(string $phone)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeTest extends TestCase
{
    public function test()
    {
        $someClass = new SomeClass();
        $someClass->setPhone('12345');
    }
}

final class SomeClass
{
    public function setPhone(string $phone)
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
        return [MethodCall::class, StaticCall::class, New_::class];
    }
    /**
     * @param MethodCall|StaticCall|New_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkipCall($node)) {
            return null;
        }
        $hasChanged = \false;
        $callParameterTypes = $this->methodParametersAndReturnTypesResolver->resolveCallParameterTypes($node);
        $callParameterNames = $this->methodParametersAndReturnTypesResolver->resolveCallParameterNames($node);
        foreach ($node->getArgs() as $key => $arg) {
            if (!$arg->value instanceof Scalar) {
                continue;
            }
            $knownParameterType = $callParameterTypes[$key] ?? null;
            if ($arg->name instanceof Identifier) {
                $argName = $arg->name->toString();
                foreach ($callParameterNames as $keyParameterNames => $callParameterName) {
                    if ($argName === $callParameterName) {
                        $knownParameterType = $callParameterTypes[$keyParameterNames] ?? null;
                        break;
                    }
                }
            }
            if (!$knownParameterType instanceof Type) {
                continue;
            }
            // remove null
            $knownParameterType = TypeCombinator::removeNull($knownParameterType);
            if ($knownParameterType instanceof StringType) {
                if ($arg->value instanceof Int_) {
                    $arg->value = new String_((string) $arg->value->value);
                    $hasChanged = \true;
                }
                if ($arg->value instanceof Float_) {
                    $arg->value = new String_((string) $arg->value->value);
                    $hasChanged = \true;
                }
            }
            if ($knownParameterType instanceof IntegerType && $arg->value instanceof String_) {
                $arg->value = new Int_((int) $arg->value->value);
                $hasChanged = \true;
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_ $callLike
     */
    private function shouldSkipCall($callLike): bool
    {
        if (!$this->isInTestClass($callLike)) {
            return \true;
        }
        if ($callLike->isFirstClassCallable()) {
            return \true;
        }
        if ($callLike->getArgs() === []) {
            return \true;
        }
        return !$this->hasStringOrNumberArguments($callLike);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_ $callLike
     */
    private function hasStringOrNumberArguments($callLike): bool
    {
        foreach ($callLike->getArgs() as $arg) {
            if ($arg->value instanceof Int_) {
                return \true;
            }
            if ($arg->value instanceof String_) {
                return \true;
            }
            if ($arg->value instanceof Float_) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_ $call
     */
    private function isInTestClass($call): bool
    {
        $callerClassReflection = $this->reflectionResolver->resolveClassReflection($call);
        if (!$callerClassReflection instanceof ClassReflection) {
            return $this->testsNodeAnalyzer->isInTestClass($call);
        }
        if ($callerClassReflection->is(BehatClassName::CONTEXT)) {
            return \true;
        }
        return $this->testsNodeAnalyzer->isInTestClass($call);
    }
}
