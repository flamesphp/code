<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp82\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Catch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\TryCatch;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\Naming\Naming\VariableNaming;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp82\Rector\MethodCall\DowngradeReflectionMethodHasPrototypeRectorTest
 */
final class DowngradeReflectionMethodHasPrototypeRector extends AbstractRector
{
    public function __construct(private readonly VariableNaming $variableNaming)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade ReflectionMethod::hasPrototype() by emulating it with getPrototype()', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run(ReflectionMethod $reflectionMethod): bool
    {
        return $reflectionMethod->hasPrototype();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run(ReflectionMethod $reflectionMethod): bool
    {
        return (function (\ReflectionMethod $reflectionMethod2): bool {
            try {
                $reflectionMethod2->getPrototype();
                return true;
            } catch (\ReflectionException) {
                return false;
            }
        })($reflectionMethod);
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
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!$this->isName($node->name, 'hasPrototype')) {
            return null;
        }
        if (!$this->isObjectType($node->var, new ObjectType('ReflectionMethod'))) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        $parameterName = $this->variableNaming->createCountedValueName('reflectionMethod', $scope);
        return new FuncCall($this->createClosure($parameterName), [new Arg($node->var)]);
    }
    private function createClosure(string $parameterName): Closure
    {
        $reflectionMethodVariable = new Variable($parameterName);
        $tryCatch = new TryCatch([new Expression(new MethodCall($reflectionMethodVariable, 'getPrototype')), new Return_(new ConstFetch(new Name('true')))], [new Catch_([new FullyQualified('ReflectionException')], null, [new Return_(new ConstFetch(new Name('false')))])]);
        return new Closure(['params' => [new Param($reflectionMethodVariable, null, new FullyQualified('ReflectionMethod'))], 'returnType' => new Identifier('bool'), 'stmts' => [$tryCatch]]);
    }
}
