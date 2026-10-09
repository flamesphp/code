<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\CallLike;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\NodeAnalyzer\CallLikeArgumentNameAdder;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\CallLike\AddNameToBooleanArgumentRectorTest
 */
final class AddNameToBooleanArgumentRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly CallLikeArgumentNameAdder $callLikeArgumentNameAdder, private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add parameter names to boolean arguments.', [new CodeSample(<<<'CODE_SAMPLE'
in_array($value, $array, true);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
in_array($value, $array, strict: true);
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [CallLike::class];
    }
    /**
     * @param CallLike $node
     */
    public function refactor(Node $node): ?Node
    {
        return $this->callLikeArgumentNameAdder->addNamesToArgs($node, fn(Expr $expr): bool => $this->valueResolver->isTrueOrFalse($expr));
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NAMED_ARGUMENTS;
    }
}
