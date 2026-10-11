<?php

declare (strict_types=1);
namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;
/**
 * @implements Rule<MethodReturnStatementsNode>
 */
#[RegisteredRule(4)]
final class TooWideMethodReturnTypehintRule implements Rule
{
    private bool $checkProtectedAndPublicMethods;
    private \PHPStan\Rules\TooWideTypehints\TooWideTypeCheck $check;
    public function __construct(
        #[AutowiredParameter('%checkTooWideReturnTypesInProtectedAndPublicMethods%')]
        bool $checkProtectedAndPublicMethods,
        \PHPStan\Rules\TooWideTypehints\TooWideTypeCheck $check
    )
    {
        $this->checkProtectedAndPublicMethods = $checkProtectedAndPublicMethods;
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if ($scope->isInTrait()) {
            return [];
        }
        $method = $node->methodReflection;
        $isFirstDeclaration = $method->getPrototype()->getDeclaringClass() === $method->declaringClass;
        if (!$method->isPrivate()) {
            if (!$method->declaringClass->isFinal() && !$method->isFinal()->yes()) {
                if (!$this->checkProtectedAndPublicMethods) {
                    return [];
                }
                if ($isFirstDeclaration) {
                    return [];
                }
            }
        }
        return $this->check->checkFunctionReturnType($node, $method->getNativeReturnType(), $method->getPhpDocReturnType(), sprintf('Method %s::%s()', $method->declaringClass->getDisplayName(), $method->getName()), !$isFirstDeclaration && !$method->isPrivate(), $scope);
    }
}
