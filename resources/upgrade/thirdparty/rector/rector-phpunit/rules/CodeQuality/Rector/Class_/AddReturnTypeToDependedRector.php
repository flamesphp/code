<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnAnalyzer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddReturnTypeToDependedRectorTest
 */
final class AddReturnTypeToDependedRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly ReturnAnalyzer $returnAnalyzer, private readonly StaticTypeMapper $staticTypeMapper, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add return type declaration to a test method that returns type', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $value = new \stdClass();

        return $value;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test(): \stdClass
    {
        $value = new \stdClass();

        return $value;
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if (!$classMethod->isPublic()) {
                continue;
            }
            if (!$this->testsNodeAnalyzer->isTestClassMethod($classMethod)) {
                continue;
            }
            // already known return type
            if ($classMethod->returnType instanceof Node) {
                continue;
            }
            $returns = $this->betterNodeFinder->findReturnsScoped($classMethod);
            if (!$this->returnAnalyzer->hasOnlyReturnWithExpr($classMethod, $returns)) {
                return null;
            }
            if (count($returns) !== 1) {
                continue;
            }
            $soleReturnExpr = $returns[0]->expr;
            if ($soleReturnExpr === null) {
                continue;
            }
            // does return a type?
            $returnedExprType = $this->nodeTypeResolver->getNativeType($soleReturnExpr);
            $returnType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($returnedExprType, TypeKind::RETURN);
            if (!$returnType instanceof Node) {
                continue;
            }
            $classMethod->returnType = $returnType;
            $hasChanged = \true;
        }
        if ($hasChanged === \false) {
            return null;
        }
        return $node;
    }
}
