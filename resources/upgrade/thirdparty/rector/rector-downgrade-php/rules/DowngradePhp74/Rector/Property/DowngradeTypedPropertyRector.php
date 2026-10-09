<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp74\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\NodeManipulator\PropertyDecorator;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp74\Rector\Property\DowngradeTypedPropertyRectorTest
 */
final class DowngradeTypedPropertyRector extends AbstractRector
{
    public function __construct(private readonly PropertyDecorator $propertyDecorator, private readonly ValueResolver $valueResolver)
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
        return new RuleDefinition('Changes property type definition from type definitions to `@var` annotations.', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private string $property;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var string
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
        $default = $node->props[0]->default;
        if ($node->type instanceof NullableType && $default instanceof Expr && $this->valueResolver->isNull($default)) {
            $node->props[0]->default = null;
        }
        $this->propertyDecorator->decorateWithDocBlock($node, $node->type);
        $node->type = null;
        return $node;
    }
}
