<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;
/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(2)]
#[ValidatesStubFiles]
final class RequireExtendsDefinitionClassRule implements Rule
{
    private \PHPStan\Rules\PhpDoc\RequireExtendsCheck $requireExtendsCheck;
    public function __construct(\PHPStan\Rules\PhpDoc\RequireExtendsCheck $requireExtendsCheck)
    {
        $this->requireExtendsCheck = $requireExtendsCheck;
    }
    public function getNodeType(): string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->classReflection;
        $extendsTags = $classReflection->getRequireExtendsTags();
        if (count($extendsTags) === 0) {
            return [];
        }
        if (!$classReflection->isInterface()) {
            return [RuleErrorBuilder::message('PHPDoc tag @phpstan-require-extends is only valid on trait or interface.')->identifier(sprintf('requireExtends.on%s', $classReflection->getClassTypeDescription()))->build()];
        }
        return $this->requireExtendsCheck->checkExtendsTags($scope, $node, $extendsTags);
    }
}
