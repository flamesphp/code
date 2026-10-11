<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Unambiguous\Rector\Expression;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as breaking a fluent setter chain into standalone calls needs a more complex approach that depends on the use case - the safe transformation differs per method return semantics.
 */
final class FluentSettersToStandaloneCallMethodRector extends AbstractRector implements DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change fluent setter chain calls, to standalone line of setters', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return (new SomeFluentClass())
            ->setName('John')
            ->setAge(30);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $someFluentClass = new SomeFluentClass();
        $someFluentClass->setName('John');
        $someFluentClass->setAge(30);

        return $someFluentClass;
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
        return [Expression::class, Return_::class];
    }
    /**
     * @param Expression|Return_ $node
     */
    public function refactor(Node $node): ?array
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as breaking a fluent setter chain needs a more complex approach that depends on the use case', self::class));
    }
}
