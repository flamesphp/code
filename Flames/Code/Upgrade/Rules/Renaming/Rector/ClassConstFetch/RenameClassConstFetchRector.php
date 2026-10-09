<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\Rector\ClassConstFetch;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Renaming\Contract\RenameClassConstFetchInterface;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameClassAndConstFetch;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameClassConstFetch;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRectorTest
 */
final class RenameClassConstFetchRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var RenameClassConstFetchInterface[]
     */
    private array $renameClassConstFetches = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace defined class constants in their calls', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
$value = SomeClass::OLD_CONSTANT;
$value = SomeClass::OTHER_OLD_CONSTANT;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$value = SomeClass::NEW_CONSTANT;
$value = DifferentClass::NEW_CONSTANT;
CODE_SAMPLE
, [new RenameClassConstFetch('SomeClass', 'OLD_CONSTANT', 'NEW_CONSTANT'), new RenameClassAndConstFetch('SomeClass', 'OTHER_OLD_CONSTANT', 'DifferentClass', 'NEW_CONSTANT')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassConstFetch::class];
    }
    /**
     * @param ClassConstFetch $node
     */
    public function refactor(Node $node): ?ClassConstFetch
    {
        foreach ($this->renameClassConstFetches as $renameClassConstFetch) {
            if (!$this->isName($node->name, $renameClassConstFetch->getOldConstant())) {
                continue;
            }
            if (!$this->isObjectType($node->class, $renameClassConstFetch->getOldObjectType())) {
                continue;
            }
            if ($renameClassConstFetch instanceof RenameClassAndConstFetch) {
                return $this->createClassAndConstFetch($renameClassConstFetch);
            }
            $node->name = new Identifier($renameClassConstFetch->getNewConstant());
            return $node;
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, RenameClassConstFetchInterface::class);
        $this->renameClassConstFetches = $configuration;
    }
    private function createClassAndConstFetch(RenameClassAndConstFetch $renameClassAndConstFetch): ClassConstFetch
    {
        return new ClassConstFetch(new FullyQualified($renameClassAndConstFetch->getNewClass()), new Identifier($renameClassAndConstFetch->getNewConstant()));
    }
}
