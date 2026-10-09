<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp82\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\NodeAnalyzer\ArgsAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @changelog https://www.php.net/manual/en/migration82.other-changes.php#migration82.other-changes.functions.spl
 *
 * @see \Flames\Code\Upgrade\DowngradePhp82\Rector\FuncCall\DowngradeIteratorCountToArrayRectorTest
 * @see https://3v4l.org/TPW7a#v8.1.31
 */
final class DowngradeIteratorCountToArrayRector extends AbstractRector
{
    public function __construct(private readonly ArgsAnalyzer $argsAnalyzer)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Ternary::class, FuncCall::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Ensure pass Traversable instance before use in iterator_count() and iterator_to_array()', [new CodeSample(<<<'CODE_SAMPLE'
function test(array|Traversable $data) {
    $c = iterator_count($data);
    $v = iterator_to_array($data);
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function test(array|Traversable $data) {
    $c = iterator_count(is_array($data) ? new ArrayIterator($data) : $data);
    $v = iterator_to_array(is_array($data) ? new ArrayIterator($data) : $data);
}
CODE_SAMPLE
)]);
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?\PhpParser\Node\Expr\FuncCall
    {
        if (!$this->isNames($node, ['iterator_count', 'iterator_to_array'])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $arg = $node->getArg('iterator', 0);
        if (!$arg instanceof Arg) {
            return null;
        }
        $type = $this->nodeTypeResolver->getType($arg->value);
        if ($this->shouldSkip($type)) {
            return null;
        }
        $position = $this->argsAnalyzer->resolveArgPosition($node->getArgs(), 'iterator', 0);
        Assert::isInstanceOf($node->args[$position], Arg::class);
        $firstValue = $node->args[$position]->value;
        $node->args[$position]->value = new Ternary($this->nodeFactory->createFuncCall('is_array', [new Arg($firstValue)]), new New_(new FullyQualified('ArrayIterator'), [new Arg($firstValue)]), $firstValue);
        return $node;
    }
    private function shouldSkip(Type $type): bool
    {
        if ($type->isArray()->yes()) {
            return \false;
        }
        $type = TypeCombinator::removeNull($type);
        return $type->isObject()->yes();
    }
}
