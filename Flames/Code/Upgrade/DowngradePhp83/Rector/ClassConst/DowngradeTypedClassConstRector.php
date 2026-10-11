<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp83\Rector\ClassConst;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst;
use Flames\Code\Upgrade\NodeManipulator\PropertyDecorator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/typed_class_constants
 *
 * @see \Flames\Code\Upgrade\DowngradePhp83\Rector\ClassConst\DowngradeTypedClassConstRectorTest
 */
final class DowngradeTypedClassConstRector extends AbstractRector
{
    public function __construct(private readonly PropertyDecorator $propertyDecorator)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassConst::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove typed class constant', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public string FOO = 'test';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @var string
     */
    public FOO = 'test';
}
CODE_SAMPLE
)]);
    }
    /**
     * @param ClassConst $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->type instanceof Node) {
            return null;
        }
        $this->propertyDecorator->decorateWithDocBlock($node, $node->type);
        $node->type = null;
        return $node;
    }
}
