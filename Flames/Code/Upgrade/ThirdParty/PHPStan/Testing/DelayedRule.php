<?php

declare (strict_types=1);
namespace PHPStan\Testing;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\DirectRegistry;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Registry;
use PHPStan\Rules\Rule;
use function get_class;
/**
 * @implements Rule<Node>
 */
final class DelayedRule implements Rule
{
    private Registry $registry;
    /** @var list<IdentifierRuleError> */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $errors = [];
    /**
     * @param Rule<covariant Node> $rule
     */
    public function __construct(Rule $rule)
    {
        $this->registry = new DirectRegistry([$rule]);
    }
    public function getNodeType(): string
    {
        return Node::class;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    public function getDelayedErrors(): array
    {
        return $this->errors;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $nodeType = get_class($node);
        foreach ($this->registry->getRules($nodeType) as $rule) {
            foreach ($rule->processNode($node, $scope) as $error) {
                $this->errors[] = $error;
            }
        }
        return [];
    }
}
