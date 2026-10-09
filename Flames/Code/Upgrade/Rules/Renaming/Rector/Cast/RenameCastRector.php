<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\Rector\Cast;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Double;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameCast;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Renaming\Rector\Cast\RenameCastRectorTest
 */
final class RenameCastRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var array<RenameCast>
     */
    private array $renameCasts = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Renames casts', [new ConfiguredCodeSample('$real = (real) $real;', '$real = (float) $real;', [new RenameCast(Double::class, Double::KIND_REAL, Double::KIND_FLOAT)])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Cast::class];
    }
    /**
     * @param Cast $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->renameCasts as $renameCast) {
            $expectedClassName = $renameCast->getFromCastExprClass();
            if (!$node instanceof $expectedClassName) {
                continue;
            }
            if ($node->getAttribute(AttributeKey::KIND) !== $renameCast->getFromCastKind()) {
                continue;
            }
            $node->setAttribute(AttributeKey::KIND, $renameCast->getToCastKind());
            $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
            $node->setAttribute('startTokenPos', -1);
            $node->setAttribute('endTokenPos', -1);
            return $node;
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsInstanceOf($configuration, RenameCast::class);
        $this->renameCasts = $configuration;
    }
}
