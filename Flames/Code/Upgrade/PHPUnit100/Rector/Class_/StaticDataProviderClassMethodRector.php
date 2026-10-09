<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit100\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\PHPUnit\NodeFinder\DataProviderClassMethodFinder;
use Flames\Code\Upgrade\Rules\Privatization\NodeManipulator\VisibilityManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\PHPUnit100\Rector\Class_\StaticDataProviderClassMethodRectorTest
 */
final class StaticDataProviderClassMethodRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    /**
     * inherited from the PHPUnit 10.0 set
     */
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=10.0');
    }
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly DataProviderClassMethodFinder $dataProviderClassMethodFinder, private readonly VisibilityManipulator $visibilityManipulator, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change data provider methods to static', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test()
    {
    }

    public function provideData()
    {
        yield [1];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test()
    {
    }

    public static function provideData()
    {
        yield [1];
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
        // 1. find all data providers
        $dataProviderClassMethods = $this->dataProviderClassMethodFinder->find($node);
        $hasChanged = \false;
        foreach ($dataProviderClassMethods as $dataProviderClassMethod) {
            if ($this->skipMethod($dataProviderClassMethod)) {
                continue;
            }
            $this->visibilityManipulator->makeStatic($dataProviderClassMethod);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function skipMethod(ClassMethod $classMethod): bool
    {
        if ($classMethod->isStatic()) {
            return \true;
        }
        if ($classMethod->stmts === null) {
            return \false;
        }
        return (bool) $this->betterNodeFinder->findFirst($classMethod->stmts, fn(Node $node): bool => $node instanceof Variable && $this->isName($node, 'this'));
    }
}
