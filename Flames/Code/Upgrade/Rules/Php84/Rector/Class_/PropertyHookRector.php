<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php84\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as property hooks are a matter of preference. The rule was never part of any set, there is no real upgrade path from getters/setters, and the hooked property mixes state and behavior in a single place, making the code harder to read. Keep the explicit getter/setter methods instead.
 */
final class PropertyHookRector extends AbstractRector implements MinPhpVersionInterface, DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace getter/setter with property hook', [new CodeSample(<<<'CODE_SAMPLE'
final class Product
{
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = ucfirst($name);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class Product
{
    public string $name
    {
        get => $this->name;
        set($value) => $this->name = ucfirst($value);
    }
}

CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as property hooks are a matter of preference. They provide no upgrade value and make the code harder to read', self::class));
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::PROPERTY_HOOKS;
    }
}
