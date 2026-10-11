<?php

declare (strict_types=1);
namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<CollectedDataNode>
 */
#[RegisteredRule(4)]
final class CallToFunctionStatementWithoutImpurePointsRule implements Rule
{
    private \PHPStan\Rules\DeadCode\PossiblyPureCallTransitivePurityResolver $purityResolver;
    public function __construct(\PHPStan\Rules\DeadCode\PossiblyPureCallTransitivePurityResolver $purityResolver)
    {
        $this->purityResolver = $purityResolver;
    }
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $pureKeys = $this->purityResolver->getPureCallableKeys($node);
        $functions = [];
        foreach ($node->get(\PHPStan\Rules\DeadCode\FunctionWithoutImpurePointsCollector::class) as $collected) {
            foreach ($collected as [$functionName]) {
                if (!isset($pureKeys[\PHPStan\Rules\DeadCode\PossiblyPureCallTransitivePurityResolver::functionKey($functionName)])) {
                    continue;
                }
                $functions[strtolower($functionName)] = $functionName;
            }
        }
        $errors = [];
        foreach ($node->get(\PHPStan\Rules\DeadCode\PossiblyPureFuncCallCollector::class) as $filePath => $data) {
            foreach ($data as [$func, $line]) {
                $lowerFunc = strtolower($func);
                if (!array_key_exists($lowerFunc, $functions)) {
                    continue;
                }
                $originalFunctionName = $functions[$lowerFunc];
                $errors[] = RuleErrorBuilder::message(sprintf('Call to function %s() on a separate line has no effect.', $originalFunctionName))->file($filePath)->line($line)->identifier('function.resultUnused')->build();
            }
        }
        return $errors;
    }
}
