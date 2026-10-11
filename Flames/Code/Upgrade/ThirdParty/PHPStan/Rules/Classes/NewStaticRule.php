<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use function strtolower;
/**
 * @implements Rule<Node\Expr\New_>
 */
#[RegisteredRule(0)]
final class NewStaticRule implements Rule
{
    private PhpVersion $phpVersion;
    private \PHPStan\Rules\Classes\ConsistentConstructorHelper $consistentConstructorHelper;
    public function __construct(PhpVersion $phpVersion, \PHPStan\Rules\Classes\ConsistentConstructorHelper $consistentConstructorHelper)
    {
        $this->phpVersion = $phpVersion;
        $this->consistentConstructorHelper = $consistentConstructorHelper;
    }
    public function getNodeType(): string
    {
        return Node\Expr\New_::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Node\Name) {
            return [];
        }
        if (!$scope->isInClass()) {
            return [];
        }
        if (strtolower($node->class->name) !== 'static') {
            return [];
        }
        $classReflection = $scope->getClassReflection();
        if ($classReflection->isFinal()) {
            return [];
        }
        $messages = [RuleErrorBuilder::message('Unsafe usage of new static().')->identifier('new.static')->tip('See: https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static')->build()];
        $consistentConstructor = $this->consistentConstructorHelper->findConsistentConstructor($classReflection);
        if ($consistentConstructor !== null) {
            return [];
        }
        if (!$classReflection->hasConstructor()) {
            return $messages;
        }
        $constructor = $classReflection->getConstructor();
        if ($constructor->getPrototype()->getDeclaringClass()->isInterface()) {
            return [];
        }
        foreach ($classReflection->getInterfaces() as $interface) {
            if ($interface->hasConstructor()) {
                return [];
            }
        }
        if ($constructor->isFinal()->yes()) {
            return [];
        }
        if ($constructor instanceof PhpMethodReflection) {
            $prototype = $constructor->getPrototype();
            if ($prototype->isAbstract()) {
                return [];
            }
        }
        if ($this->phpVersion->versionId >= 80000 && $scope->isInTrait()) {
            $traitReflection = $scope->getTraitReflection();
            if ($traitReflection->hasConstructor()) {
                $isAbstract = $traitReflection->getConstructor()->isAbstract();
                if ($isAbstract === \true || $isAbstract instanceof TrinaryLogic && $isAbstract->yes()) {
                    return [];
                }
            }
        }
        return $messages;
    }
}
