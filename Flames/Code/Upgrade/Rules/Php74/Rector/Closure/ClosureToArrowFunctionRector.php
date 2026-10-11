<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Rector\Closure;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rules\Php74\NodeAnalyzer\ClosureArrowFunctionAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php74\Rector\Closure\ClosureToArrowFunctionRectorTest
 */
final class ClosureToArrowFunctionRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ClosureArrowFunctionAnalyzer $closureArrowFunctionAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change closure to arrow function', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($meetups)
    {
        return array_filter($meetups, function (Meetup $meetup) {
            return is_object($meetup);
        });
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($meetups)
    {
        return array_filter($meetups, fn(Meetup $meetup) => is_object($meetup));
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
        return [Closure::class];
    }
    /**
     * @param Closure $node
     */
    public function refactor(Node $node): ?Node
    {
        $returnExpr = $this->closureArrowFunctionAnalyzer->matchArrowFunctionExpr($node);
        if (!$returnExpr instanceof Expr) {
            return null;
        }
        if ($node->getAttribute(AttributeKey::IS_CLOSURE_IN_ATTRIBUTE) === \true) {
            return null;
        }
        $attributes = $node->getAttributes();
        unset($attributes[AttributeKey::ORIGINAL_NODE]);
        $arrowFunction = new ArrowFunction(['params' => $node->params, 'returnType' => $node->returnType, 'byRef' => $node->byRef, 'expr' => $returnExpr], $attributes);
        if ($node->static) {
            $arrowFunction->static = \true;
        }
        $comments = $node->stmts[0]->getAttribute(AttributeKey::COMMENTS) ?? [];
        if ($comments !== []) {
            $this->mirrorComments($arrowFunction->expr, $node->stmts[0]);
            $arrowFunction->setAttribute(AttributeKey::COMMENTS, $node->stmts[0]->getComments());
        }
        return $arrowFunction;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ARROW_FUNCTION;
    }
}
