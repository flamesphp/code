<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(0)]
final class MissingMethodImplementationRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->classReflection;
        if ($classReflection->isInterface()) {
            return [];
        }
        if ($classReflection->isAbstract()) {
            return [];
        }
        $messages = [];
        try {
            $nativeMethods = $classReflection->getNativeReflection()->getMethods();
        } catch (IdentifierNotFound $e) {
            return [];
        }
        foreach ($nativeMethods as $method) {
            if (!$method->betterReflectionMethod->isAbstract()) {
                continue;
            }
            $declaringClass = new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($method->betterReflectionMethod->getImplementingClass());
            if ($declaringClass->betterReflectionClass->isInterface() && $this->isProvidedByBuiltinAncestor($classReflection, $declaringClass->betterReflectionClass->getName())) {
                // A non-abstract built-in class implementing the interface always
                // provides its methods at runtime, even when a stub reports one as
                // abstract because it is version-gated (e.g. IntlBreakIterator +
                // IteratorAggregate::getIterator(), SimpleXMLElement + RecursiveIterator).
                continue;
            }
            $classLikeDescription = 'Non-abstract class';
            if ($classReflection->isEnum()) {
                $classLikeDescription = 'Enum';
            }
            $messages[] = RuleErrorBuilder::message(sprintf('%s %s contains abstract method %s() from %s %s.', $classLikeDescription, $classReflection->getDisplayName(), $method->betterReflectionMethod->getName(), $declaringClass->betterReflectionClass->isInterface() ? 'interface' : 'class', $declaringClass->betterReflectionClass->getName()))->nonIgnorable()->identifier('method.abstract')->build();
        }
        return $messages;
    }
    private function isProvidedByBuiltinAncestor(ClassReflection $classReflection, string $interfaceName): bool
    {
        foreach ($classReflection->getParents() as $parent) {
            if (!$parent->isBuiltin()) {
                continue;
            }
            if ($parent->isAbstract()) {
                continue;
            }
            if ($parent->implementsInterface($interfaceName)) {
                return \true;
            }
        }
        return \false;
    }
}
