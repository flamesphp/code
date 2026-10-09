<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Closure;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\NeverType;
use Flames\Code\Upgrade\Enum\ClassName;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\ReturnTypeInferer;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Closure\ClosureReturnTypeFromAssertInstanceOfRectorTest
 */
final class ClosureReturnTypeFromAssertInstanceOfRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ReturnTypeInferer $returnTypeInferer, private readonly StaticTypeMapper $staticTypeMapper, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add return type to closures narrowed by assertInstanceOf() inside PHPUnit test case classes', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $callback = function (object $object) {
            $this->assertInstanceOf(SomeType::class, $object);

            return $object;
        };
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $callback = function (object $object): SomeType {
            $this->assertInstanceOf(SomeType::class, $object);

            return $object;
        };
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
        return [Closure::class];
    }
    /**
     * @param Closure $node
     */
    public function refactor(Node $node): ?Node
    {
        // type is already set
        if ($node->returnType instanceof Node) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        if (!$this->isInsideTestCaseClass($scope)) {
            return null;
        }
        // only handle closures narrowed by assertInstanceOf(); plain returns are handled by ClosureReturnTypeRector
        if (!$this->hasAssertInstanceOf($node)) {
            return null;
        }
        $closureReturnType = $this->returnTypeInferer->inferFunctionLike($node);
        // handled by other rules
        if ($closureReturnType instanceof NeverType) {
            return null;
        }
        $returnTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($closureReturnType, TypeKind::RETURN);
        if (!$returnTypeNode instanceof Node) {
            return null;
        }
        $node->returnType = $returnTypeNode;
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::SCALAR_TYPES;
    }
    private function hasAssertInstanceOf(Closure $closure): bool
    {
        return (bool) $this->betterNodeFinder->findFirst($closure->stmts, fn(Node $node): bool => ($node instanceof MethodCall || $node instanceof StaticCall) && $this->isName($node->name, 'assertInstanceOf'));
    }
    private function isInsideTestCaseClass(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        // is phpunit test case?
        return $classReflection->is(ClassName::TEST_CASE_CLASS);
    }
}
