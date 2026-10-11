<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Bool_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ElseIf_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use PHPStan\Type\MixedType;
use Flames\Code\Upgrade\Rules\CodeQuality\ValueObject\ExplicitBoolCondition;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
final readonly class ExplicitBoolConditionResolver
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ElseIf_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary $node
     */
    public function resolve($node): ?ExplicitBoolCondition
    {
        // skip short ternary
        if ($node instanceof Ternary && !$node->if instanceof Expr) {
            return null;
        }
        if ($node->cond instanceof BooleanNot) {
            $conditionNode = $node->cond->expr;
            $isNegated = \true;
        } else {
            $conditionNode = $node->cond;
            $isNegated = \false;
        }
        if ($conditionNode instanceof Bool_) {
            return null;
        }
        $conditionStaticType = $this->nodeTypeResolver->getNativeType($conditionNode);
        if ($conditionStaticType instanceof MixedType || $conditionStaticType->isBoolean()->yes()) {
            return null;
        }
        return new ExplicitBoolCondition($conditionNode, $isNegated);
    }
}
