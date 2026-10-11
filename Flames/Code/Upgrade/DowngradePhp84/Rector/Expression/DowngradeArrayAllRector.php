<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp84\Rector\Expression;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Break_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\Rules\Naming\Naming\VariableNaming;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
 *
 * @see \Flames\Code\Upgrade\DowngradePhp84\Rector\Expression\DowngradeArrayAllRectorTest
 */
final class DowngradeArrayAllRector extends AbstractRector
{
    public function __construct(private readonly VariableNaming $variableNaming)
    {
    }
    public function getNodeTypes(): array
    {
        return [Expression::class, Return_::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade array_all() to foreach loop', [new CodeSample(<<<'CODE_SAMPLE'
$found = array_all($animals, fn($animal) => str_starts_with($animal, 'c'));
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$found = true;
foreach ($animals as $animal) {
    if (!str_starts_with($animal, 'c')) {
        $found = false;
        break;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param Expression|Return_ $node
     * @return Stmt[]|null
     */
    public function refactor(Node $node): ?array
    {
        if ($node instanceof Return_ && !$node->expr instanceof FuncCall) {
            return null;
        }
        if ($node instanceof Expression && !$node->expr instanceof Assign) {
            return null;
        }
        $expr = $node instanceof Expression && $node->expr instanceof Assign ? $node->expr->expr : $node->expr;
        if (!$expr instanceof FuncCall) {
            return null;
        }
        if (!$this->isName($expr, 'array_all')) {
            return null;
        }
        if ($expr->isFirstClassCallable()) {
            return null;
        }
        $args = $expr->getArgs();
        if (count($args) !== 2) {
            return null;
        }
        if (!$args[1]->value instanceof ArrowFunction) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        $variable = $node instanceof Expression && $node->expr instanceof Assign ? $node->expr->var : new Variable($this->variableNaming->createCountedValueName('found', $scope));
        $valueCond = $args[1]->value->expr;
        $if = new If_(new BooleanNot($valueCond), ['stmts' => [new Expression(new Assign($variable, new ConstFetch(new Name('false')))), new Break_()]]);
        $result = [
            // init
            new Expression(new Assign($variable, new ConstFetch(new Name('true')))),
            // foreach loop
            new Foreach_($args[0]->value, $args[1]->value->params[0]->var, isset($args[1]->value->params[1]->var) ? ['keyVar' => $args[1]->value->params[1]->var, 'stmts' => [$if]] : ['stmts' => [$if]]),
        ];
        if ($node instanceof Return_) {
            $result[] = new Return_($variable);
        }
        return $result;
    }
}
