<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp85\Rector\Expression;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Cast\Void_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\Rules\Naming\Naming\VariableNaming;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://wiki.php.net/rfc/marking_return_value_as_important
 * @see \Flames\Code\Upgrade\DowngradePhp85\Rector\Expression\DowngradeVoidCastRectorTest
 */
final class DowngradeVoidCastRector extends AbstractRector
{
    public function __construct(private readonly VariableNaming $variableNaming)
    {
    }
    public function getNodeTypes(): array
    {
        return [Expression::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace void casts with proper handling of return values', [new CodeSample(<<<'CODE_SAMPLE'
#[\NoDiscard]
function getPhpVersion(): string
{
    return 'PHP 8.5';
}

(void) getPhpVersion();
CODE_SAMPLE
, <<<'CODE_SAMPLE'
#[\NoDiscard]
function getPhpVersion(): string
{
    return 'PHP 8.5';
}

$_void = getPhpVersion();
CODE_SAMPLE
)]);
    }
    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->expr instanceof Void_) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        $variable = new Variable($this->variableNaming->createCountedValueName('_void', $scope));
        // the assign is needed to avoid warning
        // see https://3v4l.org/ie68D#v8.5.3 vs https://3v4l.org/nLc5J#v8.5.3
        $node->expr = new Assign($variable, $node->expr->expr);
        return $node;
    }
}
