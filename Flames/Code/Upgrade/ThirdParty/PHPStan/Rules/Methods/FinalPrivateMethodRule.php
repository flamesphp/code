<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/** @implements Rule<InClassMethodNode> */
#[RegisteredRule(0)]
final class FinalPrivateMethodRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->methodReflection;
        if ($method->getName() === '__construct') {
            return [];
        }
        if ($scope->getPhpVersion()->producesWarningForFinalPrivateMethods()->no()) {
            return [];
        }
        if (!$method->isFinal()->yes() || !$method->isPrivate()) {
            return [];
        }
        return [RuleErrorBuilder::message(sprintf('Private method %s::%s() cannot be final as it is never overridden by other classes.', $method->declaringClass->getDisplayName(), $method->getName()))->identifier('method.finalPrivate')->build()];
    }
}
