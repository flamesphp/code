<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Visibility\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Rules\Privatization\NodeManipulator\VisibilityManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Visibility\Rector\ClassMethod\ExplicitPublicClassMethodRectorTest
 */
final class ExplicitPublicClassMethodRector extends AbstractRector
{
    public function __construct(private readonly VisibilityManipulator $visibilityManipulator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add explicit public method visibility', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    function foo()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function foo()
    {
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        return $this->visibilityManipulator->publicize($node);
    }
}
