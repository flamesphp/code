<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit90\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\PHPUnit\NodeFactory\AssertCallFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://github.com/sebastianbergmann/phpunit/blob/master/ChangeLog-9.0.md
 * @changelog https://github.com/sebastianbergmann/phpunit/commit/1ba2e3e1bb091acda3139f8a9259fa8161f3242d
 *
 * @see \Flames\Code\Upgrade\PHPUnit90\Rector\MethodCall\ExplicitPhpErrorApiRectorTest
 */
final class ExplicitPhpErrorApiRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    /**
     * the expectDeprecation() family was added in PHPUnit 8.4 and removed in PHPUnit 10.0
     */
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=8.4 <10.0');
    }
    /**
     * @var array<string, string>
     */
    private const array REPLACEMENTS = ['PHPUnit\Framework\TestCase\Notice' => 'expectNotice', 'PHPUnit\Framework\TestCase\Deprecated' => 'expectDeprecation', 'PHPUnit\Framework\TestCase\Error' => 'expectError', 'PHPUnit\Framework\TestCase\Warning' => 'expectWarning'];
    public function __construct(private readonly AssertCallFactory $assertCallFactory, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use explicit API for expecting PHP errors, warnings, and notices', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $this->expectException(\PHPUnit\Framework\TestCase\Deprecated::class);
        $this->expectException(\PHPUnit\Framework\TestCase\Error::class);
        $this->expectException(\PHPUnit\Framework\TestCase\Notice::class);
        $this->expectException(\PHPUnit\Framework\TestCase\Warning::class);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $this->expectDeprecation();
        $this->expectError();
        $this->expectNotice();
        $this->expectWarning();
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
        return [MethodCall::class, StaticCall::class];
    }
    /**
     * @param MethodCall|StaticCall $node
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall
     */
    public function refactor(Node $node)
    {
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, ['expectException'])) {
            return null;
        }
        foreach (self::REPLACEMENTS as $class => $method) {
            $newNode = $this->replaceExceptionWith($node, $class, $method);
            if ($newNode instanceof Node) {
                return $newNode;
            }
        }
        return null;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall
     */
    private function replaceExceptionWith($node, string $exceptionClass, string $explicitMethod)
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!isset($node->getArgs()[0])) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        if (!$this->isClassConstReference($firstArg->value, $exceptionClass)) {
            return null;
        }
        return $this->assertCallFactory->createCallWithName($node, $explicitMethod);
    }
    /**
     * Detects "SomeClass::class"
     */
    private function isClassConstReference(Expr $expr, string $className): bool
    {
        if (!$expr instanceof ClassConstFetch) {
            return \false;
        }
        if (!$this->isName($expr->name, 'class')) {
            return \false;
        }
        return $this->isName($expr->class, $className);
    }
}
