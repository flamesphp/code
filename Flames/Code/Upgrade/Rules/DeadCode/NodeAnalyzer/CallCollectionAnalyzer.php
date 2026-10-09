<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\StaticTypeMapper\Resolver\ClassNameFromObjectTypeResolver;
final readonly class CallCollectionAnalyzer
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param StaticCall[]|MethodCall[]|NullsafeMethodCall[] $calls
     */
    public function isExists(array $calls, string $classMethodName, string $className): bool
    {
        foreach ($calls as $call) {
            $callerRoot = $call instanceof StaticCall ? $call->class : $call->var;
            $callerType = $this->nodeTypeResolver->getType($callerRoot);
            // a nullsafe call caller is nullable by design; drop null to resolve the object class
            if ($call instanceof NullsafeMethodCall) {
                $callerType = TypeCombinator::removeNull($callerType);
            }
            $callerTypeClassName = ClassNameFromObjectTypeResolver::resolve($callerType);
            if ($callerTypeClassName === null) {
                // the caller scope is unreachable, e.g. behind mutual recursion, so the type
                // resolves to never; the call still exists in code, keep the method to be safe
                if ($callerType instanceof NeverType && $this->shouldSkip($call, $classMethodName)) {
                    return \true;
                }
                // handle fluent by $this->bar()->baz()->qux()
                // that methods don't have return type
                if ($callerType instanceof MixedType && !$callerType->isExplicitMixed()) {
                    $cloneCallerRoot = clone $callerRoot;
                    $isFluent = \false;
                    // init
                    $methodCallNames = [];
                    // first append
                    $methodCallNames[] = (string) $this->nodeNameResolver->getName($call->name);
                    while ($cloneCallerRoot instanceof MethodCall) {
                        $methodCallNames[] = (string) $this->nodeNameResolver->getName($cloneCallerRoot->name);
                        if ($cloneCallerRoot->var instanceof Variable && $cloneCallerRoot->var->name === 'this') {
                            $isFluent = \true;
                            break;
                        }
                        $cloneCallerRoot = $cloneCallerRoot->var;
                    }
                    if ($isFluent && in_array($classMethodName, $methodCallNames, \true)) {
                        return \true;
                    }
                }
                continue;
            }
            if ($this->isSelfStatic($call) && $this->shouldSkip($call, $classMethodName)) {
                return \true;
            }
            if ($callerTypeClassName !== $className) {
                continue;
            }
            if ($this->shouldSkip($call, $classMethodName)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall $call
     */
    private function isSelfStatic($call): bool
    {
        return $call instanceof StaticCall && $call->class instanceof Name && in_array($call->class->toString(), [ObjectReference::SELF, ObjectReference::STATIC], \true);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall $call
     */
    private function shouldSkip($call, string $classMethodName): bool
    {
        if (!$call->name instanceof Identifier) {
            return \true;
        }
        // the method is used
        return $this->nodeNameResolver->isName($call->name, $classMethodName);
    }
}
