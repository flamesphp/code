<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\InterpolatedString;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnAnalyzer;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRectorTest
 */
final class StringReturnTypeFromStrictStringReturnsRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard, private readonly BetterNodeFinder $betterNodeFinder, private readonly ReturnAnalyzer $returnAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add string return type based on returned strict string values', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function foo($condition, $value)
    {
        if ($value) {
            return 'yes';
        }

        return strtoupper($value);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function foo($condition, $value): string;
    {
        if ($value) {
            return 'yes';
        }

        return strtoupper($value);
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
        return [ClassMethod::class, Function_::class];
    }
    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // already added → skip
        if ($node->returnType instanceof Node) {
            return null;
        }
        $returns = $this->betterNodeFinder->findReturnsScoped($node);
        if (!$this->returnAnalyzer->hasOnlyReturnWithExpr($node, $returns)) {
            return null;
        }
        // handled by another rule
        if ($this->hasAlwaysStringScalarReturn($returns)) {
            return null;
        }
        // anything that return strict string, but no strings only
        if (!$this->isAlwaysStringStrictType($returns)) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        if ($this->shouldSkipClassMethodForOverride($node, $scope)) {
            return null;
        }
        $node->returnType = new Identifier('string');
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_70;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     */
    private function shouldSkipClassMethodForOverride($functionLike, Scope $scope): bool
    {
        if (!$functionLike instanceof ClassMethod) {
            return \false;
        }
        return $this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($functionLike, $scope);
    }
    /**
     * @param Return_[] $returns
     */
    private function hasAlwaysStringScalarReturn(array $returns): bool
    {
        $found = array_all($returns, fn($return) => $return->expr instanceof String_ || $return->expr instanceof InterpolatedString);
        return $found;
    }
    /**
     * @param Return_[] $returns
     */
    private function isAlwaysStringStrictType(array $returns): bool
    {
        foreach ($returns as $return) {
            // void return
            if (!$return->expr instanceof Expr) {
                return \false;
            }
            $exprType = $this->nodeTypeResolver->getNativeType($return->expr);
            if (!$exprType->isString()->yes()) {
                return \false;
            }
        }
        return \true;
    }
}
