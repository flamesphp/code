<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp74\Rector\Identical;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp74\Rector\Identical\DowngradeFreadFwriteFalsyToNegationRectorTest
 */
final class DowngradeFreadFwriteFalsyToNegationRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const array FUNC_FREAD_FWRITE = ['fread', 'fwrite'];
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes fread() or fwrite() compare to false to negation check', [new CodeSample(<<<'CODE_SAMPLE'
fread($handle, $length) === false;
fwrite($fp, '1') === false;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
!fread($handle, $length);
!fwrite($fp, '1');
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Identical::class];
    }
    /**
     * @param Identical $node
     */
    public function refactor(Node $node): ?Node
    {
        $compareExpr = $this->getCompareValue($node);
        if (!$compareExpr instanceof Expr) {
            return null;
        }
        if (!$this->valueResolver->isFalse($compareExpr)) {
            return null;
        }
        return new BooleanNot($this->getFunction($node));
    }
    private function getCompareValue(Identical $identical): ?Expr
    {
        if ($identical->left instanceof FuncCall && $this->isNames($identical->left, self::FUNC_FREAD_FWRITE)) {
            return $identical->right;
        }
        if (!$identical->right instanceof FuncCall) {
            return null;
        }
        if (!$this->isNames($identical->right, self::FUNC_FREAD_FWRITE)) {
            return null;
        }
        return $identical->left;
    }
    private function getFunction(Identical $identical): FuncCall
    {
        /** @var FuncCall $funcCall */
        $funcCall = $identical->left instanceof FuncCall ? $identical->left : $identical->right;
        return $funcCall;
    }
}
