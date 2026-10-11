<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\NodeAnalyzer\ArgsAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use ReflectionFunction;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://www.php.net/manual/en/function.number-format.php#refsect1-function.number-format-changelog
 *
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeNumberFormatNoFourthArgRectorTest
 */
final class DowngradeNumberFormatNoFourthArgRector extends AbstractRector
{
    public function __construct(private readonly ArgsAnalyzer $argsAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade number_format arg to fill 4th arg when only 3rd arg filled', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return number_format(1000, 2, ',');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return number_format(1000, 2, ',', ',');
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
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        $reflectionFunction = new ReflectionFunction('number_format');
        $node->args[3] = new Arg(new String_($reflectionFunction->getParameters()[3]->getDefaultValue()));
        return $node;
    }
    private function shouldSkip(FuncCall $funcCall): bool
    {
        if (!$this->isName($funcCall, 'number_format')) {
            return \true;
        }
        $args = $funcCall->getArgs();
        if ($this->argsAnalyzer->hasNamedArg($args)) {
            return \true;
        }
        if (isset($args[3])) {
            return \true;
        }
        return !isset($args[2]);
    }
}
