<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\AliasedObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer\NullableTypeAnalyzer;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRectorTest
 */
final class FlipTypeControlToUseExclusiveTypeRector extends AbstractRector
{
    public function __construct(private readonly NullableTypeAnalyzer $nullableTypeAnalyzer, private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Flip type control from null compare to use exclusive instanceof object', [new CodeSample(<<<'CODE_SAMPLE'
function process(?DateTime $dateTime)
{
    if ($dateTime === null) {
        return;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function process(?DateTime $dateTime)
{
    if (! $dateTime instanceof DateTime) {
        return;
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
        return [Identical::class, NotIdentical::class];
    }
    /**
     * @param Identical|NotIdentical $node
     */
    public function refactor(Node $node): ?Node
    {
        $expr = $this->matchNullComparedExpr($node);
        if (!$expr instanceof Expr) {
            return null;
        }
        $nullableObjectType = $this->nullableTypeAnalyzer->resolveNullableObjectType($expr);
        if (!$nullableObjectType instanceof ObjectType) {
            return null;
        }
        return $this->processConvertToExclusiveType($nullableObjectType, $expr, $node);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical $binaryOp
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_
     */
    private function processConvertToExclusiveType(ObjectType $objectType, Expr $expr, $binaryOp)
    {
        $fullyQualifiedType = $objectType instanceof ShortenedObjectType || $objectType instanceof AliasedObjectType ? $objectType->getFullyQualifiedName() : $objectType->getClassName();
        $instanceof = new Instanceof_($expr, new FullyQualified($fullyQualifiedType));
        if ($binaryOp instanceof NotIdentical) {
            return $instanceof;
        }
        return new BooleanNot($instanceof);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical $binaryOp
     */
    private function matchNullComparedExpr($binaryOp): ?Expr
    {
        if ($this->valueResolver->isNull($binaryOp->left)) {
            return $binaryOp->right;
        }
        if ($this->valueResolver->isNull($binaryOp->right)) {
            return $binaryOp->left;
        }
        return null;
    }
}
