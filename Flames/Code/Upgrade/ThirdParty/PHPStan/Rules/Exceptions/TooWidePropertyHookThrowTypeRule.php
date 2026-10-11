<?php

declare (strict_types=1);
namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;
use function ucfirst;
/**
 * @implements Rule<PropertyHookReturnStatementsNode>
 */
final class TooWidePropertyHookThrowTypeRule implements Rule
{
    private \PHPStan\Rules\Exceptions\TooWideThrowTypeCheck $check;
    private bool $checkProtectedAndPublicMethods;
    public function __construct(\PHPStan\Rules\Exceptions\TooWideThrowTypeCheck $check, bool $checkProtectedAndPublicMethods)
    {
        $this->check = $check;
        $this->checkProtectedAndPublicMethods = $checkProtectedAndPublicMethods;
    }
    public function getNodeType(): string
    {
        return PropertyHookReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $statementResult = $node->statementResult;
        $hookReflection = $node->hookReflection;
        if ($hookReflection->getPropertyHookName() === null) {
            throw new ShouldNotHappenException();
        }
        $propertyReflection = $node->propertyReflection;
        $throwType = $hookReflection->getThrowType();
        if ($throwType === null) {
            return [];
        }
        if (!$propertyReflection->isPrivate() && !$propertyReflection->getDeclaringClass()->isFinal() && !$propertyReflection->isFinal()->yes() && !$hookReflection->isFinal()->yes() && !$this->checkProtectedAndPublicMethods) {
            return [];
        }
        $errors = [];
        foreach ($this->check->check($throwType, $statementResult->getThrowPoints()) as $throwClass) {
            $errors[] = RuleErrorBuilder::message(sprintf('%s hook for property %s::$%s has %s in PHPDoc @throws tag but it\'s not thrown.', ucfirst($hookReflection->getPropertyHookName()), $hookReflection->declaringClass->getDisplayName(), $hookReflection->hookForProperty, $throwClass))->identifier('throws.unusedType')->build();
        }
        return $errors;
    }
}
