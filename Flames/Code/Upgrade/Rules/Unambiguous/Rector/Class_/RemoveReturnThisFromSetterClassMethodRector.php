<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Unambiguous\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as removing "return $this" from a setter needs a more complex approach that depends on the use case - the setter may be part of a fluent public API that callers rely on.
 */
final class RemoveReturnThisFromSetterClassMethodRector extends AbstractRector implements DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove return $this from setter method, to make explicit setter without return value. Goal is to make code unambiguous with one way to set value', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private $name;

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    private $name;

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Class_>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Class_
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as removing "return $this" from a setter needs a more complex approach that depends on the use case', self::class));
    }
}
