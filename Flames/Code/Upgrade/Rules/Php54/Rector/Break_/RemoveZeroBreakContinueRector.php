<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php54\Rector\Break_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Break_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Continue_;
use PHPStan\Type\Constant\ConstantIntegerType;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php54\Rector\Break_\RemoveZeroBreakContinueRectorTest
 */
final class RemoveZeroBreakContinueRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_ZERO_BREAK;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove 0 from break and continue', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($random)
    {
        continue 0;
        break 0;

        $five = 5;
        continue $five;

        break $random;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($random)
    {
        continue;
        break;

        $five = 5;
        continue 5;

        break;
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
        return [Break_::class, Continue_::class];
    }
    /**
     * @param Break_|Continue_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->num instanceof Expr) {
            return null;
        }
        if ($node->num instanceof Int_) {
            $number = $this->valueResolver->getValue($node->num);
            if ($number > 1) {
                return null;
            }
            if ($number === 0) {
                $node->num = null;
                return $node;
            }
            return null;
        }
        if ($node->num instanceof Variable) {
            return $this->processVariableNum($node, $node->num);
        }
        return null;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Break_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Continue_ $stmt
     */
    private function processVariableNum($stmt, Variable $numVariable): ?Node
    {
        $staticType = $this->getType($numVariable);
        if ($staticType->isConstantValue()->yes()) {
            if ($staticType instanceof ConstantIntegerType) {
                if ($staticType->getValue() === 0) {
                    $stmt->num = null;
                    return $stmt;
                }
                if ($staticType->getValue() > 0) {
                    $stmt->num = new Int_($staticType->getValue());
                    return $stmt;
                }
            }
            return $stmt;
        }
        // remove variable
        $stmt->num = null;
        return null;
    }
}
