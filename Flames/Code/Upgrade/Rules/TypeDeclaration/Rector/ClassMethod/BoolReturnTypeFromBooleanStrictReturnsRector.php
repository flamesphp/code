<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnAnalyzer;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanStrictReturnsRectorTest
 */
final class BoolReturnTypeFromBooleanStrictReturnsRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider, private readonly ValueResolver $valueResolver, private readonly BetterNodeFinder $betterNodeFinder, private readonly ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard, private readonly ReturnAnalyzer $returnAnalyzer, private readonly ExprAnalyzer $exprAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add bool return type based on strict bool returns type operations', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function resolve($first, $second)
    {
        return $first > $second;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function resolve($first, $second): bool
    {
        return $first > $second;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @funcCall array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class];
    }
    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $scope = ScopeFetcher::fetch($node);
        if ($this->shouldSkip($node, $scope)) {
            return null;
        }
        $returns = $this->betterNodeFinder->findReturnsScoped($node);
        if (!$this->returnAnalyzer->hasOnlyReturnWithExpr($node, $returns)) {
            return null;
        }
        // handled in another rule
        if ($this->hasOnlyBooleanConstExprs($returns)) {
            return null;
        }
        // handled in another rule
        if (!$this->hasOnlyBoolScalarReturnExprs($returns)) {
            return null;
        }
        $node->returnType = new Identifier('bool');
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::SCALAR_TYPES;
    }
    /**
     * @param ClassMethod|Function_|Closure $node
     */
    private function shouldSkip(Node $node, Scope $scope): bool
    {
        // already has the type, skip
        if ($node->returnType instanceof Node) {
            return \true;
        }
        return $node instanceof ClassMethod && $this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($node, $scope);
    }
    /**
     * @param Return_[] $returns
     */
    private function hasOnlyBoolScalarReturnExprs(array $returns): bool
    {
        foreach ($returns as $return) {
            if (!$return->expr instanceof Expr) {
                return \false;
            }
            if ($this->exprAnalyzer->isBoolExpr($return->expr)) {
                continue;
            }
            if ($return->expr instanceof FuncCall && $this->isNativeBooleanReturnTypeFuncCall($return->expr)) {
                continue;
            }
            return \false;
        }
        return \true;
    }
    private function isNativeBooleanReturnTypeFuncCall(FuncCall $funcCall): bool
    {
        $functionName = $this->getName($funcCall);
        if (!is_string($functionName)) {
            return \false;
        }
        $name = new Name($functionName);
        if (!$this->reflectionProvider->hasFunction($name, null)) {
            return \false;
        }
        $functionReflection = $this->reflectionProvider->getFunction($name, null);
        if (!$functionReflection->isBuiltin()) {
            return \false;
        }
        $found = array_all($functionReflection->getVariants(), fn($extendedParametersAcceptor) => $extendedParametersAcceptor->getNativeReturnType()->isBoolean()->yes());
        return $found;
    }
    /**
     * @param Return_[] $returns
     */
    private function hasOnlyBooleanConstExprs(array $returns): bool
    {
        foreach ($returns as $return) {
            if (!$return->expr instanceof ConstFetch) {
                return \false;
            }
            if (!$this->valueResolver->isTrueOrFalse($return->expr)) {
                return \false;
            }
        }
        return \true;
    }
}
