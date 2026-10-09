<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\FuncCallToStaticCall;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Transform\Rector\FuncCall\FuncCallToStaticCallRectorTest
 */
final class FuncCallToStaticCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var FuncCallToStaticCall[]
     */
    private array $funcCallsToStaticCalls = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turn defined function call to static method call', [new ConfiguredCodeSample('view("...", []);', 'SomeClass::render("...", []);', [new FuncCallToStaticCall('view', 'SomeStaticClass', 'render')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->funcCallsToStaticCalls as $funcCallToStaticCall) {
            if (!$this->isName($node, $funcCallToStaticCall->getOldFuncName())) {
                continue;
            }
            return $this->nodeFactory->createStaticCall($funcCallToStaticCall->getNewClassName(), $funcCallToStaticCall->getNewMethodName(), $node->args);
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, FuncCallToStaticCall::class);
        $this->funcCallsToStaticCalls = $configuration;
    }
}
