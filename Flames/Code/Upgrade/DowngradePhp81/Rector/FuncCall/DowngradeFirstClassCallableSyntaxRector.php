<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/first_class_callable_syntax
 *
 * @see \Flames\Code\Upgrade\DowngradePhp81\Rector\FuncCall\DowngradeFirstClassCallableSyntaxRectorTest
 */
final class DowngradeFirstClassCallableSyntaxRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace variadic placeholders usage by Closure::fromCallable()', [new CodeSample(<<<'CODE_SAMPLE'
$cb = strlen(...);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$cb = \Closure::fromCallable('strlen');
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class, MethodCall::class, StaticCall::class];
    }
    /**
     * @param FuncCall|MethodCall|StaticCall $node
     */
    public function refactor(Node $node): ?StaticCall
    {
        if (!$node->isFirstClassCallable()) {
            return null;
        }
        $callbackExpr = $this->createCallback($node);
        return $this->createClosureFromCallableCall($callbackExpr);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr
     */
    private function createCallback($node)
    {
        if ($node instanceof FuncCall) {
            return $node->name instanceof Name ? new String_($node->name->toString()) : $node->name;
        }
        if ($node instanceof MethodCall) {
            $object = $node->var;
            $method = $node->name instanceof Identifier ? new String_($node->name->toString()) : $node->name;
            return new Array_([new ArrayItem($object), new ArrayItem($method)]);
        }
        // StaticCall
        $class = $node->class instanceof Name ? new ClassConstFetch($node->class, 'class') : $node->class;
        $method = $node->name instanceof Identifier ? new String_($node->name->toString()) : $node->name;
        return $this->nodeFactory->createArray([$class, $method]);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $expr
     */
    private function createClosureFromCallableCall($expr): StaticCall
    {
        return new StaticCall(new FullyQualified('Closure'), 'fromCallable', [new Arg($expr)]);
    }
}
