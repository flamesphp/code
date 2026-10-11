<?php

declare (strict_types=1);
namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;
/** @implements Rule<ClassConst> */
#[RegisteredRule(0)]
final class FinalPrivateConstantRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassConst::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            throw new ShouldNotHappenException();
        }
        $classReflection = $scope->getClassReflection();
        if (!(bool) ($node->flags & \PhpParser\Modifiers::FINAL)) {
            return [];
        }
        if (!(bool) ($node->flags & \PhpParser\Modifiers::PRIVATE)) {
            return [];
        }
        $errors = [];
        foreach ($node->consts as $classConstNode) {
            $errors[] = RuleErrorBuilder::message(sprintf('Private constant %s::%s() cannot be final as it is never overridden by other classes.', $classReflection->getDisplayName(), $classConstNode->name->name))->identifier('classConstant.finalPrivate')->nonIgnorable()->build();
        }
        return $errors;
    }
}
