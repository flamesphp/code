<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\Property;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\PropertyItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\Property\SplitGroupedPropertiesRectorTest
 */
final class SplitGroupedPropertiesRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Separate grouped properties to own lines', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var string
     */
    public $isIt, $isIsThough;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var string
     */
    public $isIt;

    /**
     * @var string
     */
    public $isIsThough;
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
     * @return Property[]|null
     */
    public function refactor(Node $node): ?array
    {
        $allProperties = $node->props;
        if (count($allProperties) === 1) {
            return null;
        }
        /** @var PropertyItem $firstPropertyProperty */
        $firstPropertyProperty = array_shift($allProperties);
        $node->props = [$firstPropertyProperty];
        $nextProperties = [];
        foreach ($allProperties as $allProperty) {
            $nextProperties[] = new Property($node->flags, [$allProperty], $node->getAttributes());
        }
        return array_merge([$node], $nextProperties);
    }
}
