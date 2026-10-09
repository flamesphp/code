<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as a docblock "@property" is public by design, while the native property it created was "private" - the access level changed and could break code that relied on the public property.
 */
final class DynamicDocBlockPropertyToNativePropertyRector extends AbstractRector implements DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turn dynamic docblock properties on class with no parents to explicit ones', [new CodeSample(<<<'CODE_SAMPLE'
/**
 * @property SomeDependency $someDependency
 */
#[\AllowDynamicProperties]
final class SomeClass
{
    public function __construct()
    {
        $this->someDependency = new SomeDependency();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    private SomeDependency $someDependency;

    public function __construct()
    {
        $this->someDependency = new SomeDependency();
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
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as it turned a public "@property" into a private native property, changing the access level', self::class));
    }
}
