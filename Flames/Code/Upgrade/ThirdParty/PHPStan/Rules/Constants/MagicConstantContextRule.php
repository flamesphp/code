<?php

declare (strict_types=1);
namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Scalar\MagicConst;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\MagicConstantParamDefaultVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/** @implements Rule<MagicConst> */
#[RegisteredRule(0)]
final class MagicConstantContextRule implements Rule
{
    public function getNodeType(): string
    {
        return MagicConst::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        // test cases https://3v4l.org/ZUvvr
        if ($node instanceof MagicConst\Class_) {
            if ($scope->isInClass()) {
                return [];
            }
            return [RuleErrorBuilder::message(sprintf('Magic constant %s is always empty outside a class.', '__CLASS__'))->identifier('magicConstant.outOfClass')->build()];
        } elseif ($node instanceof MagicConst\Trait_) {
            if ($scope->isInTrait()) {
                return [];
            }
            return [RuleErrorBuilder::message(sprintf('Magic constant %s is always empty outside a trait.', '__TRAIT__'))->identifier('magicConstant.outOfTrait')->build()];
        } elseif ($node instanceof MagicConst\Method || $node instanceof MagicConst\Function_) {
            if ($scope->getFunctionName() !== null) {
                return [];
            }
            if ($scope->isInAnonymousFunction()) {
                return [];
            }
            if ((bool) $node->getAttribute(MagicConstantParamDefaultVisitor::ATTRIBUTE_NAME)) {
                return [];
            }
            return [RuleErrorBuilder::message(sprintf('Magic constant %s is always empty outside a function.', $node->getName()))->identifier('magicConstant.outOfFunction')->build()];
        } elseif ($node instanceof MagicConst\Namespace_) {
            if ($scope->getNamespace() === null) {
                return [RuleErrorBuilder::message(sprintf('Magic constant %s is always empty in global namespace.', '__NAMESPACE__'))->identifier('magicConstant.outOfNamespace')->build()];
            }
        }
        return [];
    }
}
