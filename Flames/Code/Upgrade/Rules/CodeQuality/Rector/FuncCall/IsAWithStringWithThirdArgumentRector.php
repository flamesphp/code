<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\IsAWithStringWithThirdArgumentRectorTest
 */
final class IsAWithStringWithThirdArgumentRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Complete missing 3rd argument in case is_a() function in case of strings', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(string $value)
    {
        return is_a($value, 'stdClass');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(string $value)
    {
        return is_a($value, 'stdClass', true);
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
        if (!$this->isName($node, 'is_a')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (isset($node->getArgs()[2])) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        $firstArgumentStaticType = $this->getType($firstArg->value);
        if (!$firstArgumentStaticType->isString()->yes()) {
            return null;
        }
        $node->args[2] = new Arg($this->nodeFactory->createTrue());
        return $node;
    }
}
