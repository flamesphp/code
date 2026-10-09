<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Rector\String_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\StringToClassConstant;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Transform\Rector\String_\StringToClassConstantRectorTest
 */
final class StringToClassConstantRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var StringToClassConstant[]
     */
    private array $stringsToClassConstants = [];
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change strings to specific constants', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
final class SomeSubscriber
{
    public static function getSubscribedEvents()
    {
        return ['compiler.post_dump' => 'compile'];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeSubscriber
{
    public static function getSubscribedEvents()
    {
        return [\Yet\AnotherClass::CONSTANT => 'compile'];
    }
}
CODE_SAMPLE
, [new StringToClassConstant('compiler.post_dump', 'Yet\AnotherClass', 'CONSTANT')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [String_::class];
    }
    /**
     * @param String_ $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->stringsToClassConstants as $stringToClassConstant) {
            if (!$this->valueResolver->isValue($node, $stringToClassConstant->getString())) {
                continue;
            }
            return $this->nodeFactory->createClassConstFetch($stringToClassConstant->getClass(), $stringToClassConstant->getConstant());
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, StringToClassConstant::class);
        $this->stringsToClassConstants = $configuration;
    }
}
