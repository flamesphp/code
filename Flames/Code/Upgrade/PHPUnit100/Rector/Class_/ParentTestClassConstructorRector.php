<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit100\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see https://github.com/sebastianbergmann/phpunit/issues/3975
 * @see https://github.com/sebastianbergmann/phpunit/commit/705874f1b867fd99865e43cb5eaea4e6d141582f
 *
 * @see \Flames\Code\Upgrade\PHPUnit100\Rector\Class_\ParentTestClassConstructorRectorTest
 */
final class ParentTestClassConstructorRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    /**
     * inherited from the PHPUnit 10.0 set
     */
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=10.0');
    }
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('PHPUnit\Framework\TestCase requires a parent constructor call', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeHelper extends TestCase
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeHelper extends TestCase
{
    public function __construct()
    {
        parent::__construct(static::class);
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
        if ($this->shouldSkipClass($node)) {
            return null;
        }
        // it already has a constructor, skip as it might require specific tweaking
        if ($node->getMethod(MethodName::CONSTRUCT)) {
            return null;
        }
        $constructorClassMethod = new ClassMethod(MethodName::CONSTRUCT);
        $constructorClassMethod->flags |= Modifiers::PUBLIC;
        $constructorClassMethod->stmts[] = new Expression($this->createParentConstructorCall());
        $node->stmts = array_merge([$constructorClassMethod], $node->stmts);
        return $node;
    }
    private function createParentConstructorCall(): StaticCall
    {
        $staticClassConstFetch = new ClassConstFetch(new Name('static'), 'class');
        return new StaticCall(new Name('parent'), MethodName::CONSTRUCT, [new Arg($staticClassConstFetch)]);
    }
    private function shouldSkipClass(Class_ $class): bool
    {
        if ($class->isAbstract()) {
            return \true;
        }
        if ($class->isAnonymous()) {
            return \true;
        }
        $className = $this->getName($class);
        // loaded automatically by PHPUnit
        if (str_ends_with((string) $className, 'Test')) {
            return \true;
        }
        if (str_ends_with((string) $className, 'TestCase')) {
            return \true;
        }
        return (bool) $class->getAttribute('hasRemovedFinalConstruct');
    }
}
