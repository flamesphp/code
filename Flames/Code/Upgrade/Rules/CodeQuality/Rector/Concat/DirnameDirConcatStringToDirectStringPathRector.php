<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Concat;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\MagicConst\Dir;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Concat\DirnameDirConcatStringToDirectStringPathRectorTest
 */
final class DirnameDirConcatStringToDirectStringPathRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change dirname() and string concat, to __DIR__ and direct string path', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $path = dirname(__DIR__) . '/vendor/autoload.php';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $path = __DIR__ . '/../vendor/autoload.php';
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Concat::class];
    }
    /**
     * @param Concat $node
     */
    public function refactor(Node $node): ?Concat
    {
        if (!$node->left instanceof FuncCall || !$this->isName($node->left, 'dirname')) {
            return null;
        }
        if (!$node->right instanceof String_) {
            return null;
        }
        $dirnameFuncCall = $node->left;
        if ($dirnameFuncCall->isFirstClassCallable()) {
            return null;
        }
        // avoid multiple dir nesting for now
        if (count($dirnameFuncCall->getArgs()) !== 1) {
            return null;
        }
        $firstArg = $dirnameFuncCall->getArgs()[0];
        if (!$firstArg->value instanceof Dir) {
            return null;
        }
        $string = $node->right;
        if (str_contains($string->value, '/')) {
            // linux paths
            $string->value = '/../' . ltrim($string->value, '/');
            $node->left = new Dir();
            return $node;
        }
        if (str_contains($string->value, '\\')) {
            // windows paths
            $string->value = '\..\\' . ltrim($string->value, '\\');
            $node->left = new Dir();
            return $node;
        }
        return null;
    }
}
