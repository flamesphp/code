<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as too niche and of little practical value. It only fires when array_map() gets an inline closure with an explicit return type, where static analysis already knows the item type without the docblock.
 */
final class AddReturnArrayDocblockBasedOnArrayMapRector extends AbstractRector implements DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add @return array docblock based on array_map() return strict type', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function getItems(array $items)
    {
        return array_map(function ($item): int {
            return $item->id;
        }, $items);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @return int[]
     */
    public function getItems(array $items)
    {
        return array_map(function ($item): int {
            return $item->id;
        }, $items);
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class];
    }
    /**
     * @param ClassMethod|Function_ $node
     * @return null|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\ClassMethod
     */
    public function refactor(Node $node): never
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as too niche and of little practical value. The item type is already known from the inline closure return type', self::class));
    }
}
