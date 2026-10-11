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
final class TooWideMethodParameterOutTypeRule implements Rule
{
    private \PHPStan\Rules\TooWideTypehints\TooWideParameterOutTypeCheck $check;
    private bool $checkProtectedAndPublicMethods;
    public function __construct(
        \PHPStan\Rules\TooWideTypehints\TooWideParameterOutTypeCheck $check,
        #[AutowiredParameter('%checkTooWideParameterOutInProtectedAndPublicMethods%')]
        bool $checkProtectedAndPublicMethods
    )
    {
        $this->check = $check;
        $this->checkProtectedAndPublicMethods = $checkProtectedAndPublicMethods;
    }
    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $inMethod = $node->methodReflection;
        if (!$inMethod->isPrivate()) {
            if (!$inMethod->declaringClass->isFinal() && !$inMethod->isFinal()->yes()) {
                if (!$this->checkProtectedAndPublicMethods) {
                    return [];
                }
            }
        }
        return $this->check->check($node->getStartLine(), $node->executionEnds, $node->returnStatements, $inMethod->getParameters(), sprintf('Method %s::%s()', $inMethod->declaringClass->getDisplayName(), $inMethod->getName()));
    }
}
