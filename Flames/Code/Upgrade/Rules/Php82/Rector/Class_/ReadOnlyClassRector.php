<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php82\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Rules\Php82\NodeManipulator\ReadonlyClassManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php82\Rector\Class_\ReadOnlyClassRectorTest
 */
final class ReadOnlyClassRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ReadonlyClassManipulator $readonlyClassManipulator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Decorate read-only class with `readonly` attribute', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function __construct(
        private readonly string $name
    ) {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final readonly class SomeClass
{
    public function __construct(
        private string $name
    ) {
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
        if ($node->isAnonymous()) {
            return null;
        }
        return $this->readonlyClassManipulator->process($node);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::READONLY_CLASS;
    }
}
