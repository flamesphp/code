<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\While_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\While_;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer\NullableTypeAnalyzer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\While_\WhileNullableToInstanceofRectorTest
 */
final class WhileNullableToInstanceofRector extends AbstractRector
{
    public function __construct(private readonly NullableTypeAnalyzer $nullableTypeAnalyzer, private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change while null compare to strict instanceof check', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(?SomeClass $someClass)
    {
        while ($someClass !== null) {
            // do something
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(?SomeClass $someClass)
    {
        while ($someClass instanceof SomeClass) {
            // do something
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
        return [While_::class, Do_::class];
    }
    /**
     * @param While_|Do_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->cond instanceof Assign) {
            return null;
        }
        if ($node->cond instanceof NotIdentical) {
            return $this->refactorNotIdentical($node, $node->cond);
        }
        $condNullableObjectType = $this->nullableTypeAnalyzer->resolveNullableObjectType($node->cond);
        if (!$condNullableObjectType instanceof Type) {
            return null;
        }
        $node->cond = $this->createInstanceof($node->cond, $condNullableObjectType);
        return $node;
    }
    private function createInstanceof(Expr $expr, ObjectType $objectType): Instanceof_
    {
        $fullyQualified = new FullyQualified($objectType->getClassName());
        return new Instanceof_($expr, $fullyQualified);
    }
    /**
     * @param \PhpParser\Node\Stmt\While_|\PhpParser\Node\Stmt\Do_ $while
     * @return \PhpParser\Node\Stmt\While_|\PhpParser\Node\Stmt\Do_|null
     */
    private function refactorNotIdentical($while, NotIdentical $notIdentical)
    {
        if (!$this->valueResolver->isNull($notIdentical->right)) {
            return null;
        }
        $condNullableObjectType = $this->nullableTypeAnalyzer->resolveNullableObjectType($notIdentical->left);
        if (!$condNullableObjectType instanceof ObjectType) {
            return null;
        }
        $while->cond = $this->createInstanceof($notIdentical->left, $condNullableObjectType);
        return $while;
    }
}
