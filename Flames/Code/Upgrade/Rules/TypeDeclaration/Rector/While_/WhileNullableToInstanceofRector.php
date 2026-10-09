<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\While_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer\NullableTypeAnalyzer;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
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
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_ $while
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_|null
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
