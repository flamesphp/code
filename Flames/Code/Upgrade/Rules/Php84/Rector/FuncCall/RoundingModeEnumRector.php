<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php84\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php84\Rector\FuncCall\RoundingModeEnumRectorTest
 */
final class RoundingModeEnumRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace rounding mode constant to RoundMode enum in `round()`', [new CodeSample(<<<'CODE_SAMPLE'
round(1.5, 0, PHP_ROUND_HALF_UP);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
round(1.5, 0, RoundingMode::HalfAwayFromZero);
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?FuncCall
    {
        if (!$this->isName($node, 'round')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) !== 3) {
            return null;
        }
        if (!isset($args[2])) {
            return null;
        }
        $modeArg = $args[2]->value;
        $hasChanged = \false;
        if ($modeArg instanceof ConstFetch) {
            $enumCase = match ($modeArg->name->toString()) {
                'PHP_ROUND_HALF_UP' => 'HalfAwayFromZero',
                'PHP_ROUND_HALF_DOWN' => 'HalfTowardsZero',
                'PHP_ROUND_HALF_EVEN' => 'HalfEven',
                'PHP_ROUND_HALF_ODD' => 'HalfOdd',
                default => null,
            };
            if ($enumCase === null) {
                return null;
            }
            $args[2]->value = new ClassConstFetch(new FullyQualified('RoundingMode'), $enumCase);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ROUNDING_MODES;
    }
}
