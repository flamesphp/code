<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryMinus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/add_str_starts_with_and_ends_with_functions
 *
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeStrEndsWithRectorTest
 */
final class DowngradeStrEndsWithRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade str_ends_with() to strncmp() version', [new CodeSample('str_ends_with($haystack, $needle);', '"" === $needle || ("" !== $haystack && 0 === substr_compare($haystack, $needle, -\strlen($needle)));')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class, BooleanNot::class];
    }
    /**
     * @param FuncCall|BooleanNot $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof FuncCall && $this->isName($node->name, 'str_ends_with')) {
            return new Identical($this->createSubstrCompareFuncCall($node), new Int_(0));
        }
        if ($node instanceof BooleanNot) {
            $funcCall = $node->expr;
            if ($funcCall instanceof FuncCall && $this->isName($funcCall->name, 'str_ends_with')) {
                return new NotIdentical($this->createSubstrCompareFuncCall($funcCall), new Int_(0));
            }
        }
        return null;
    }
    private function createSubstrCompareFuncCall(FuncCall $funcCall): FuncCall
    {
        $args = $funcCall->getArgs();
        $strlenFuncCall = $this->createStrlenFuncCall($args[1]->value);
        $args[] = new Arg(new UnaryMinus($strlenFuncCall));
        return new FuncCall(new Name('substr_compare'), $args);
    }
    private function createStrlenFuncCall(Expr $expr): FuncCall
    {
        return new FuncCall(new Name('strlen'), [new Arg($expr)]);
    }
}
