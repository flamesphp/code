<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Rector\ConstFetch;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\ConstFetchToClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see Flames\Code\Upgrade\Rules\Transform\Rector\ConstFetch\ConstFetchToClassConstFetchConstFetchToClassConstFetchTest
 */
final class ConstFetchToClassConstFetchRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var ConstFetchToClassConstFetch[]
     */
    private array $constFetchToClassConsts = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change const fetch to class const fetch', [new ConfiguredCodeSample('$x = CONTEXT_COURSE', '$x = course::LEVEL', [new ConstFetchToClassConstFetch('CONTEXT_COURSE', 'course', 'LEVEL')])]);
    }
    public function getNodeTypes(): array
    {
        return [ConstFetch::class];
    }
    /**
     * @param ConstFetch $node
     */
    public function refactor(Node $node): ?ClassConstFetch
    {
        foreach ($this->constFetchToClassConsts as $constFetchToClassConst) {
            if (!$this->isName($node, $constFetchToClassConst->getOldConstName())) {
                continue;
            }
            return $this->nodeFactory->createClassConstFetch($constFetchToClassConst->getNewClassName(), $constFetchToClassConst->getNewConstName());
        }
        return null;
    }
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, ConstFetchToClassConstFetch::class);
        $this->constFetchToClassConsts = $configuration;
    }
}
