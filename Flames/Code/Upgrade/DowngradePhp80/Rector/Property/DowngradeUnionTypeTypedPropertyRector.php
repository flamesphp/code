<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\Property;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType;
use Flames\Code\Upgrade\NodeManipulator\PropertyDecorator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\Property\DowngradeUnionTypeTypedPropertyRectorTest
 */
final class DowngradeUnionTypeTypedPropertyRector extends AbstractRector
{
    public function __construct(private readonly PropertyDecorator $PropertyDecorator)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Removes union type property type definition, adding `@var` annotations instead.', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private string|int $property;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var string|int
     */
    private $property;
}
CODE_SAMPLE
)]);
    }
    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->type === null) {
            return null;
        }
        if (!$this->shouldRemoveProperty($node)) {
            return null;
        }
        $this->PropertyDecorator->decorateWithDocBlock($node, $node->type);
        $node->type = null;
        return $node;
    }
    private function shouldRemoveProperty(Property $property): bool
    {
        if (!$property->type instanceof Node) {
            return \false;
        }
        // Check it is the union type
        return $property->type instanceof UnionType;
    }
}
