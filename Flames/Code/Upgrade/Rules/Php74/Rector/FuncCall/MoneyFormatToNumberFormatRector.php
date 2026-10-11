<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\NodeAnalyzer\ArgsAnalyzer;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\MoneyFormatToNumberFormatRectorTest
 */
final class MoneyFormatToNumberFormatRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ArgsAnalyzer $argsAnalyzer, private readonly ValueResolver $valueResolver)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_MONEY_FORMAT;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change `money_format()` to equivalent `number_format()`', [new CodeSample(<<<'CODE_SAMPLE'
$value = money_format('%i', $value);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$value = number_format(round($value, 2, PHP_ROUND_HALF_ODD), 2, '.', '');
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
    public function refactor(Node $node): ?FuncCall
    {
        if (!$this->isName($node, 'money_format')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        if ($this->argsAnalyzer->hasNamedArg($args)) {
            return null;
        }
        $formatValue = $args[0]->value;
        if (!$this->valueResolver->isValue($formatValue, '%i')) {
            return null;
        }
        return $this->warpInNumberFormatFuncCall($node, $args[1]->value);
    }
    private function warpInNumberFormatFuncCall(FuncCall $funcCall, Expr $expr): FuncCall
    {
        $roundFuncCall = $this->nodeFactory->createFuncCall('round', [$expr, new Int_(2), new ConstFetch(new Name('PHP_ROUND_HALF_ODD'))]);
        $funcCall->name = new Name('number_format');
        $funcCall->args = [new Arg($roundFuncCall), new Arg(new Int_(2)), new Arg(new String_('.')), new Arg(new String_(''))];
        return $funcCall;
    }
}
