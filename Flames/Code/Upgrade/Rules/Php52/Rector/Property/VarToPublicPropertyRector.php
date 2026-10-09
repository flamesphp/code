<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php52\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Rules\Privatization\NodeManipulator\VisibilityManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php52\Rector\Property\VarToPublicPropertyRectorTest
 */
final class VarToPublicPropertyRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly VisibilityManipulator $visibilityManipulator)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::PROPERTY_MODIFIER;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change property modifier from `var` to `public`', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeController
{
    var $name = 'Tom';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeController
{
    public $name = 'Tom';
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }
    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Node
    {
        // explicitly public
        if ($node->flags !== 0) {
            return null;
        }
        $this->visibilityManipulator->makePublic($node);
        return $node;
    }
}
