<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp84\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog wiki.php.net/rfc/correctly_name_the_rounding_mode_and_make_it_an_enum
 *
 * @see \Flames\Code\Upgrade\DowngradePhp84\Rector\FuncCall\DowngradeRoundingModeEnumRectorTest
 */
final class DowngradeRoundingModeEnumRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace RoundingMode enum to rounding mode constant in round()', [new CodeSample(<<<'CODE_SAMPLE'
round(1.5, 0, RoundingMode::HalfAwayFromZero);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
round(1.5, 0, PHP_ROUND_HALF_UP);
CODE_SAMPLE
)]);
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node, 'round')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $arg = $node->getArg('mode', 2);
        if (!$arg instanceof Arg) {
            return null;
        }
        $modeArg = $arg->value;
        $hasChanged = \false;
        if ($modeArg instanceof ClassConstFetch && $modeArg->class instanceof FullyQualified && $this->isName($modeArg->class, 'RoundingMode')) {
            if (!$modeArg->name instanceof Identifier) {
                return null;
            }
            $constantName = match ($modeArg->name->name) {
                'HalfAwayFromZero' => 'PHP_ROUND_HALF_UP',
                'HalfTowardsZero' => 'PHP_ROUND_HALF_DOWN',
                'HalfEven' => 'PHP_ROUND_HALF_EVEN',
                'HalfOdd' => 'PHP_ROUND_HALF_ODD',
                default => null,
            };
            if ($constantName === null) {
                return null;
            }
            $arg->value = new ConstFetch(new FullyQualified($constantName));
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
