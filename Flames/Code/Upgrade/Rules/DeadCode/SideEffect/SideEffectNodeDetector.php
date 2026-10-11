<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\SideEffect;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class SideEffectNodeDetector
{
    /**
     * @var array<class-string<Expr>>
     */
    private const array CALL_EXPR_SIDE_EFFECT_NODE_TYPES = [MethodCall::class, New_::class, NullsafeMethodCall::class, StaticCall::class];
    public function __construct(private \Flames\Code\Upgrade\Rules\DeadCode\SideEffect\PureFunctionDetector $pureFunctionDetector, private BetterNodeFinder $betterNodeFinder, private NodeTypeResolver $nodeTypeResolver, private NodeNameResolver $nodeNameResolver)
    {
    }
    public function detect(Expr $expr): bool
    {
        if ($expr instanceof Assign) {
            return \true;
        }
        return (bool) $this->betterNodeFinder->findFirst($expr, $this->detectCallExpr(...));
    }
    public function detectCallExpr(Node $node): bool
    {
        if (!$node instanceof Expr) {
            return \false;
        }
        if ($node instanceof StaticCall && $this->isClassCallerThrowable($node)) {
            return \false;
        }
        if ($node instanceof New_ && $this->isPhpParser($node)) {
            return \false;
        }
        if (($node instanceof MethodCall || $node instanceof StaticCall) && $this->isTestMock($node)) {
            return \false;
        }
        $exprClass = $node::class;
        if (in_array($exprClass, self::CALL_EXPR_SIDE_EFFECT_NODE_TYPES, \true)) {
            return \true;
        }
        if ($node instanceof FuncCall) {
            return !$this->pureFunctionDetector->detect($node);
        }
        if ($node instanceof Variable || $node instanceof ArrayDimFetch) {
            $variable = $this->resolveVariable($node);
            // variables don't have side effects
            return !$variable instanceof Variable;
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     */
    private function isTestMock($node): bool
    {
        $objectType = new ObjectType(\PHPUnit\Framework\TestCase::class);
        $nodeCaller = $node instanceof MethodCall ? $node->var : $node->class;
        if (!$this->nodeTypeResolver->isObjectType($nodeCaller, $objectType)) {
            return \false;
        }
        return $this->nodeNameResolver->isNames($node->name, ['createMock', 'createStub']);
    }
    private function isPhpParser(New_ $new): bool
    {
        if (!$new->class instanceof FullyQualified) {
            return \false;
        }
        $className = $new->class->toString();
        $namespace = Strings::before($className, '\\', 1);
        return $namespace === 'PhpParser';
    }
    private function isClassCallerThrowable(StaticCall $staticCall): bool
    {
        $class = $staticCall->class;
        if (!$class instanceof Name) {
            return \false;
        }
        $throwableType = new ObjectType('Throwable');
        $type = new ObjectType($class->toString());
        return $throwableType->isSuperTypeOf($type)->yes();
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable $expr
     */
    private function resolveVariable($expr): ?Variable
    {
        while ($expr instanceof ArrayDimFetch) {
            $expr = $expr->var;
        }
        if (!$expr instanceof Variable) {
            return null;
        }
        return $expr;
    }
}
