<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\Instanceof_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see https://wiki.php.net/rfc/stringable
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\Instanceof_\DowngradeInstanceofStringableRectorTest
 */
final class DowngradeInstanceofStringableRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('change instanceof Stringable to method_exists', [new CodeSample(<<<'CODE_SAMPLE'
$obj instanceof \Stringable;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
is_object($obj) && method_exists($obj, '__toString');
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Instanceof_::class];
    }
    /**
     * @param Instanceof_ $node
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd
     */
    public function refactor(Node $node)
    {
        if (!$node->class instanceof FullyQualified) {
            return null;
        }
        if (!$this->isName($node->class, 'Stringable')) {
            return null;
        }
        $nativeExprType = $this->nodeTypeResolver->getNativeType($node->expr);
        $funcCall = $this->nodeFactory->createFuncCall('method_exists', [$node->expr, new String_('__toString')]);
        if ($nativeExprType->isObject()->yes()) {
            return $funcCall;
        }
        return new BooleanAnd($this->nodeFactory->createFuncCall('is_object', [$node->expr]), $funcCall);
    }
}
