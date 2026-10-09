<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\NodeTypeResolver\TypeAnalyzer\StringTypeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\ConsistentImplodeRectorTest
 */
final class ConsistentImplodeRector extends AbstractRector
{
    public function __construct(private readonly StringTypeAnalyzer $stringTypeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes various implode forms to consistent one', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run(array $items)
    {
        $itemsAsStrings = implode($items);
        $itemsAsStrings = implode($items, '|');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run(array $items)
    {
        $itemsAsStrings = implode('', $items);
        $itemsAsStrings = implode('|', $items);
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
        if (!$this->isNames($node, ['implode', 'join'])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (count($node->getArgs()) === 1) {
            // complete default value ''
            $node->args[1] = $node->getArgs()[0];
            $node->args[0] = new Arg(new String_(''));
            return $node;
        }
        if (count($node->getArgs()) !== 2) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        $firstArgumentValue = $firstArg->value;
        $firstArgumentType = $this->getType($firstArgumentValue);
        if ($firstArgumentType->isString()->yes()) {
            return null;
        }
        $secondArg = $node->getArgs()[1];
        if ($this->stringTypeAnalyzer->isStringOrUnionStringOnlyType($secondArg->value)) {
            $node->args = array_reverse($node->getArgs());
            return $node;
        }
        return null;
    }
}
