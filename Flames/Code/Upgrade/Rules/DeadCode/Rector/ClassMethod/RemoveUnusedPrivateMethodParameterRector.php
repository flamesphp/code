<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Rules\DeadCode\NodeCollector\UnusedParameterResolver;
use Flames\Code\Upgrade\Rules\DeadCode\NodeManipulator\PrivateMethodParamRemover;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRectorTest
 */
final class RemoveUnusedPrivateMethodParameterRector extends AbstractRector
{
    public function __construct(private readonly UnusedParameterResolver $unusedParameterResolver, private readonly PrivateMethodParamRemover $privateMethodParamRemover)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused parameter, if not required by interface or parent class', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private function run($value, $value2)
    {
         $this->value = $value;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    private function run($value)
    {
         $this->value = $value;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if (!$classMethod->isPrivate()) {
                continue;
            }
            $unusedParameters = $this->unusedParameterResolver->resolve($classMethod);
            if ($unusedParameters === []) {
                continue;
            }
            if ($this->privateMethodParamRemover->removeParams($node, $classMethod, $unusedParameters)) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
