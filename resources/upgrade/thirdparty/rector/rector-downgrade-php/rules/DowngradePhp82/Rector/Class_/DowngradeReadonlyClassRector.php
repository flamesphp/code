<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp82\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\DowngradePhp82\NodeManipulator\DowngradeReadonlyClassManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/readonly_classes
 *
 * @see \Flames\Code\Upgrade\DowngradePhp82\Rector\Class_\DowngradeReadonlyClassRectorTest
 */
final class DowngradeReadonlyClassRector extends AbstractRector
{
    public function __construct(private readonly DowngradeReadonlyClassManipulator $downgradeReadonlyClassManipulator)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove "readonly" class type, decorate all properties to "readonly"', [new CodeSample(<<<'CODE_SAMPLE'
final readonly class SomeClass
{
    public string $foo;

    public function __construct()
    {
        $this->foo = 'foo';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public readonly string $foo;

    public function __construct()
    {
        $this->foo = 'foo';
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->isAnonymous()) {
            return null;
        }
        return $this->downgradeReadonlyClassManipulator->process($node);
    }
}
