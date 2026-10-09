<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\StmtsAwareInterface;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Nop;
use Flames\Code\Upgrade\Contract\Rector\HTMLAverseRectorInterface;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\DeclareStrictTypeFinder;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\StmtsAwareInterface\DeclareStrictTypesTestsRectorTest
 */
final class DeclareStrictTypesTestsRector extends AbstractRector implements HTMLAverseRectorInterface, MinPhpVersionInterface
{
    public function __construct(private readonly DeclareStrictTypeFinder $declareStrictTypeFinder, private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add `declare(strict_types=1)` to PHPUnit test class file', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTestWithoutStrict extends TestCase
{
    public function test()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SomeTestWithoutStrict extends TestCase
{
    public function test()
    {
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
        return [FileNode::class];
    }
    /**
     * @param FileNode $node
     */
    public function refactor(Node $node): ?FileNode
    {
        // when first stmt is Declare_, verify if there is strict_types definition already,
        // as multiple declare is allowed, with declare(strict_types=1) only allowed on very first stmt
        if ($this->declareStrictTypeFinder->hasDeclareStrictTypes($node)) {
            return null;
        }
        if (!$this->hasPHPUnitTestClass($node->stmts)) {
            return null;
        }
        $declareStrictTypes = $this->nodeFactory->createDeclaresStrictType();
        $node->stmts = array_merge([$declareStrictTypes, new Nop()], $node->stmts);
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_70;
    }
    /**
     * @param Stmt[] $nodes
     */
    private function hasPHPUnitTestClass(array $nodes): bool
    {
        $class = $this->betterNodeFinder->findFirstNonAnonymousClass($nodes);
        if (!$class instanceof Class_) {
            return \false;
        }
        return $this->testsNodeAnalyzer->isInTestClass($class);
    }
}
