<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\BooleanAnd;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer\NullableTypeAnalyzer;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\BooleanAnd\BinaryOpNullableToInstanceofRectorTest
 */
final class BinaryOpNullableToInstanceofRector extends AbstractRector
{
    public function __construct(private readonly NullableTypeAnalyzer $nullableTypeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change && and || between nullable objects to instanceof compares', [new CodeSample(<<<'CODE_SAMPLE'
function someFunction(?SomeClass $someClass)
{
    if ($someClass && $someClass->someMethod()) {
        return 'yes';
    }

    return 'no';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function someFunction(?SomeClass $someClass)
{
    if ($someClass instanceof SomeClass && $someClass->someMethod()) {
        return 'yes';
    }

    return 'no';
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [BooleanAnd::class, BooleanOr::class];
    }
    /**
     * @param BooleanAnd|BooleanOr $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->left instanceof Assign || $node->right instanceof Assign) {
            return null;
        }
        if ($node instanceof BooleanOr) {
            return $this->processNegationBooleanOr($node);
        }
        return $this->processNullableInstance($node);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr $node
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr
     */
    private function processNullableInstance($node)
    {
        $nullableObjectType = $this->nullableTypeAnalyzer->resolveNullableObjectType($node->left);
        $hasChanged = \false;
        if ($nullableObjectType instanceof ObjectType) {
            $node->left = $this->createExprInstanceof($node->left, $nullableObjectType);
            $hasChanged = \true;
        }
        $nullableObjectType = $this->nullableTypeAnalyzer->resolveNullableObjectType($node->right);
        if ($nullableObjectType instanceof ObjectType) {
            $node->right = $this->createExprInstanceof($node->right, $nullableObjectType);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function processNegationBooleanOr(BooleanOr $booleanOr): ?BooleanOr
    {
        $hasChanged = \false;
        if ($booleanOr->left instanceof BooleanNot) {
            $nullableObjectType = $this->nullableTypeAnalyzer->resolveNullableObjectType($booleanOr->left->expr);
            if ($nullableObjectType instanceof ObjectType) {
                $booleanOr->left->expr = $this->createExprInstanceof($booleanOr->left->expr, $nullableObjectType);
                $hasChanged = \true;
            }
        }
        if ($booleanOr->right instanceof BooleanNot) {
            $nullableObjectType = $this->nullableTypeAnalyzer->resolveNullableObjectType($booleanOr->right->expr);
            if ($nullableObjectType instanceof ObjectType) {
                $booleanOr->right->expr = $this->createExprInstanceof($booleanOr->right->expr, $nullableObjectType);
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $booleanOr;
        }
        /** @var BooleanOr|null $result */
        $result = $this->processNullableInstance($booleanOr);
        return $result;
    }
    private function createExprInstanceof(Expr $expr, ObjectType $objectType): Instanceof_
    {
        $fullyQualified = new FullyQualified($objectType->getClassName());
        return new Instanceof_($expr, $fullyQualified);
    }
}
