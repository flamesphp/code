<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Arguments\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\Rules\Arguments\ArgumentDefaultValueReplacer;
use Flames\Code\Upgrade\Rules\Arguments\ValueObject\ReplaceFuncCallArgumentDefaultValue;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Arguments\Rector\FuncCall\FunctionArgumentDefaultValueReplacerRectorTest
 */
final class FunctionArgumentDefaultValueReplacerRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var ReplaceFuncCallArgumentDefaultValue[]
     */
    private array $replacedArguments = [];
    public function __construct(private readonly ArgumentDefaultValueReplacer $argumentDefaultValueReplacer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Streamline the operator arguments of `version_compare` function', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
version_compare(PHP_VERSION, '5.6', 'gte');
CODE_SAMPLE
, <<<'CODE_SAMPLE'
version_compare(PHP_VERSION, '5.6', 'ge');
CODE_SAMPLE
, [new ReplaceFuncCallArgumentDefaultValue('version_compare', 2, 'gte', 'ge')])]);
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
    public function refactor(Node $node): ?\PhpParser\Node\Expr\FuncCall
    {
        $hasChanged = \false;
        foreach ($this->replacedArguments as $replacedArgument) {
            if (!$this->isName($node->name, $replacedArgument->getFunction())) {
                continue;
            }
            $changedNode = $this->argumentDefaultValueReplacer->processReplaces($node, $replacedArgument);
            if ($changedNode instanceof Node) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, ReplaceFuncCallArgumentDefaultValue::class);
        $this->replacedArguments = $configuration;
    }
}
