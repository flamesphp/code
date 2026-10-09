<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ClosureUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\MergeWithCallableAndWillReturnRectorTest
 */
final class MergeWithCallableAndWillReturnRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly ValueResolver $valueResolver, private readonly StaticTypeMapper $staticTypeMapper, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Merge split mocking method ->with($this->callback(...)) and ->willReturn(expr) to single ->willReturnCallback() call', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $this->createMock('SomeClass')
            ->expects($this->once())
            ->method('someMethod')
            ->with($this->callback(function (array $args): bool {
                return true;
            }))
            ->willReturn(['some item']);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $this->createMock('SomeClass')
            ->expects($this->once())
            ->method('someMethod')
            ->willReturnCallback(function (array $args): array {
                return ['some item'];
            });
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<MethodCall>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        if (!$this->isName($node->name, 'willReturn')) {
            return null;
        }
        $parentCaller = $node->var;
        if (!$parentCaller instanceof MethodCall) {
            return null;
        }
        if (!$this->isName($parentCaller->name, 'with')) {
            return null;
        }
        $willReturnMethodCall = $node;
        $withMethodCall = $parentCaller;
        $callbackMethodCall = $this->matchFirstArgCallbackMethodCall($withMethodCall);
        if (!$callbackMethodCall instanceof MethodCall) {
            return null;
        }
        $innerClosure = $callbackMethodCall->getArgs()[0]->value;
        if (!$innerClosure instanceof Closure) {
            return null;
        }
        if ($innerClosure->stmts === []) {
            return null;
        }
        if (!$this->isLastStmtReturnTrue($innerClosure)) {
            return null;
        }
        /** @var Return_ $return */
        $return = $innerClosure->stmts[count($innerClosure->stmts) - 1];
        $returnedExpr = $willReturnMethodCall->getArgs()[0]->value;
        $return->expr = $returnedExpr;
        $parentCaller->name = new Identifier('willReturnCallback');
        $parentCaller->args = [new Arg($innerClosure)];
        foreach ($this->resolveExternalUses($innerClosure, $returnedExpr) as $closureUse) {
            $innerClosure->uses[] = $closureUse;
        }
        $returnedExprType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($returnedExpr);
        $innerClosure->returnType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($returnedExprType, TypeKind::RETURN);
        return $parentCaller;
    }
    private function matchFirstArgCallbackMethodCall(MethodCall $withMethodCall): ?MethodCall
    {
        $firstArgValue = $withMethodCall->getArgs()[0]->value;
        if (!$firstArgValue instanceof MethodCall) {
            return null;
        }
        if (!$this->isName($firstArgValue->name, 'callback')) {
            return null;
        }
        return $firstArgValue;
    }
    /**
     * @return ClosureUse[]
     */
    private function resolveExternalUses(Closure $innerClosure, Expr $returnedExpr): array
    {
        $paramNames = [];
        foreach ($innerClosure->getParams() as $param) {
            $paramNames[] = $this->getName($param);
        }
        $existingUseNames = [];
        foreach ($innerClosure->uses as $existingUse) {
            $existingUseNames[] = $this->getName($existingUse->var);
        }
        $closureUses = [];
        $seenNames = [];
        /** @var Variable[] $variables */
        $variables = $this->betterNodeFinder->findInstancesOf($returnedExpr, [Variable::class]);
        foreach ($variables as $variable) {
            $variableName = $this->getName($variable);
            if (!is_string($variableName)) {
                continue;
            }
            if ($variableName === 'this') {
                continue;
            }
            if (in_array($variableName, $paramNames, \true)) {
                continue;
            }
            if (in_array($variableName, $existingUseNames, \true)) {
                continue;
            }
            if (in_array($variableName, $seenNames, \true)) {
                continue;
            }
            $seenNames[] = $variableName;
            $closureUses[] = new ClosureUse(new Variable($variableName));
        }
        return $closureUses;
    }
    private function isLastStmtReturnTrue(Closure $closure): bool
    {
        $lastStmt = $closure->stmts[count($closure->stmts) - 1];
        if (!$lastStmt instanceof Return_) {
            return \false;
        }
        if (!$lastStmt->expr instanceof Node) {
            return \false;
        }
        return $this->valueResolver->isTrue($lastStmt->expr);
    }
}
