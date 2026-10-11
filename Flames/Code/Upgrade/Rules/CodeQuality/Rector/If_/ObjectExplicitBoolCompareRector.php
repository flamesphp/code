<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ElseIf_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\CodeQuality\NodeAnalyzer\ExplicitBoolConditionResolver;
use Flames\Code\Upgrade\Rules\CodeQuality\ValueObject\ExplicitBoolCondition;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRectorTest
 */
final class ObjectExplicitBoolCompareRector extends AbstractRector
{
    public function __construct(private readonly ExplicitBoolConditionResolver $explicitBoolConditionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Make nullable object if conditions more explicit', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeController
{
    public function run(?\stdClass $item)
    {
        if (!$item) {
            return 'empty';
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeController
{
    public function run(?\stdClass $item)
    {
        if (!$item instanceof \stdClass) {
            return 'empty';
        }
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
        return [If_::class, ElseIf_::class, Ternary::class];
    }
    /**
     * @param If_|ElseIf_|Ternary $node
     */
    public function refactor(Node $node): ?Node
    {
        $explicitBoolCondition = $this->explicitBoolConditionResolver->resolve($node);
        if (!$explicitBoolCondition instanceof ExplicitBoolCondition) {
            return null;
        }
        $expr = $explicitBoolCondition->getConditionNode();
        $objectType = $this->nodeTypeResolver->matchNullableTypeOfSpecificType($expr, ObjectType::class);
        if (!$objectType instanceof ObjectType) {
            return null;
        }
        $node->cond = $this->resolveNullable($explicitBoolCondition->isNegated(), $expr, $objectType);
        return $node;
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_
     */
    private function resolveNullable(bool $isNegated, Expr $expr, ObjectType $objectType)
    {
        $fullyQualified = new FullyQualified($objectType->getClassName());
        $instanceof = new Instanceof_($expr, $fullyQualified);
        return $isNegated ? new BooleanNot($instanceof) : $instanceof;
    }
}
