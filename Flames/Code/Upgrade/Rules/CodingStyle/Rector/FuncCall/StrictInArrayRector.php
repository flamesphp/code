<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\NodeTypeResolver\TypeComparator\TypeComparator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\StrictInArrayRectorTest
 */
final class StrictInArrayRector extends AbstractRector
{
    public function __construct(private readonly TypeComparator $typeComparator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Set in_array strict to true when defined on similar type', [new CodeSample(<<<'CODE_SAMPLE'
class BothStrings
{
    public function run(string $value)
    {
        return in_array($value, ['one', 'two', 'three']);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class BothStrings
{
    public function run(string $value)
    {
        return in_array($value, ['one', 'two', 'three'], true);
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
        if (!$this->isName($node, 'in_array')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) !== 2) {
            return null;
        }
        $firstArgType = $this->nodeTypeResolver->getNativeType($args[0]->value);
        $secondArgType = $this->nodeTypeResolver->getNativeType($args[1]->value);
        if (!$secondArgType->isArray()->yes()) {
            return null;
        }
        if ($this->typeComparator->isSubtype($secondArgType->getIterableValueType(), $firstArgType)) {
            $node->args[] = new Arg($this->nodeFactory->createTrue());
            return $node;
        }
        return null;
    }
}
