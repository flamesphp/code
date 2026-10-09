<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanNot;

use PhpParser\Node;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * Replace negated boolean literals with their simplified equivalents
 *
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanNot\ReplaceConstantBooleanNotRectorTest
 */
final class ReplaceConstantBooleanNotRector extends AbstractRector
{
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace negated boolean literals (!false, !true) with their simplified equivalents (true, false)', [new CodeSample(<<<'CODE_SAMPLE'
if (!false) {
    return 'always true';
}

if (!true) {
    return 'never reached';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
if (true) {
    return 'always true';
}

if (false) {
    return 'never reached';
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [BooleanNot::class];
    }
    /**
     * @param BooleanNot $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->valueResolver->isFalse($node->expr)) {
            return new ConstFetch(new Name('true'));
        }
        if ($this->valueResolver->isTrue($node->expr)) {
            return new ConstFetch(new Name('false'));
        }
        return null;
    }
}
