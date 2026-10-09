<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\Rector\ConstFetch;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Validation\RectorAssert;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Renaming\Rector\ConstFetch\RenameConstantRectorTest
 */
final class RenameConstantRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var array<string, string>
     */
    private array $oldToNewConstants = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace constant by new ones', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        return MYSQL_ASSOC;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        return MYSQLI_ASSOC;
    }
}
CODE_SAMPLE
, ['MYSQL_ASSOC' => 'MYSQLI_ASSOC', 'OLD_CONSTANT' => 'NEW_CONSTANT'])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ConstFetch::class];
    }
    /**
     * @param ConstFetch $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->oldToNewConstants as $oldConstant => $newConstant) {
            if (!$this->isName($node->name, $oldConstant)) {
                continue;
            }
            $node->name = new Name($newConstant);
            return $node;
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allString(array_keys($configuration));
        Assert::allString($configuration);
        foreach ($configuration as $oldConstant => $newConstant) {
            RectorAssert::constantName($oldConstant);
            RectorAssert::constantName($newConstant);
        }
        /** @var array<string, string> $configuration */
        $this->oldToNewConstants = $configuration;
    }
}
