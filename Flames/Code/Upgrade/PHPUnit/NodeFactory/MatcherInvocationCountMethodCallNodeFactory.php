<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\PHPUnit\Enum\ConsecutiveVariable;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
/**
 * Handle silent rename "getInvocationCount()" to "numberOfInvocations()" in PHPUnit 10
 * https://github.com/sebastianbergmann/phpunit/commit/2ba8b7fded44a1a75cf5712a3b7310a8de0b6bb8#diff-3b464bb32b9187dd2d047fd1c21773aa32c19b20adebc083e1a49267c524a980
 */
final readonly class MatcherInvocationCountMethodCallNodeFactory
{
    private const string GET_INVOCATION_COUNT = 'getInvocationCount';
    private const string NUMBER_OF_INVOCATIONS = 'numberOfInvocations';
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }
    public function create(): MethodCall
    {
        $invocationMethodName = $this->getInvocationMethodName();
        $matcherVariable = new Variable(ConsecutiveVariable::MATCHER);
        return new MethodCall($matcherVariable, new Identifier($invocationMethodName));
    }
    private function getInvocationMethodName(): string
    {
        if (!$this->reflectionProvider->hasClass(PHPUnitClassName::INVOCATION_ORDER)) {
            // fallback to PHPUnit 9
            return self::GET_INVOCATION_COUNT;
        }
        $invocationOrderClassReflection = $this->reflectionProvider->getClass(PHPUnitClassName::INVOCATION_ORDER);
        // phpunit 10 naming
        if ($invocationOrderClassReflection->hasNativeMethod(self::GET_INVOCATION_COUNT)) {
            return self::GET_INVOCATION_COUNT;
        }
        // phpunit 9 naming
        return self::NUMBER_OF_INVOCATIONS;
    }
}
