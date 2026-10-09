<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\Variable;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php70\Rector\Variable\WrapVariableVariableNameInCurlyBracesRectorTest
 */
final class WrapVariableVariableNameInCurlyBracesRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::WRAP_VARIABLE_VARIABLE;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Ensure variable variables are wrapped in curly braces', [new CodeSample(<<<'CODE_SAMPLE'
function run($foo)
{
    global $$foo->bar;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function run($foo)
{
    global ${$foo->bar};
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Variable::class];
    }
    /**
     * @param Variable $node
     */
    public function refactor(Node $node): ?Node
    {
        $nodeName = $node->name;
        if (!$nodeName instanceof PropertyFetch && !$nodeName instanceof Variable) {
            return null;
        }
        if ($node->getEndTokenPos() !== $nodeName->getEndTokenPos()) {
            return null;
        }
        if ($nodeName instanceof PropertyFetch) {
            return new Variable(new PropertyFetch($nodeName->var, $nodeName->name));
        }
        return new Variable(new Variable($nodeName->name));
    }
}
