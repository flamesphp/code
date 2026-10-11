<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit110\Rector\CallLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * The assertContainsOnly*() methods were added and assertContainsOnly() deprecated in PHPUnit 11.5
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/6055
 * @see https://github.com/sebastianbergmann/phpunit/blob/11.5.0/ChangeLog-11.5.md
 *
 * @see \Flames\Code\Upgrade\PHPUnit110\Rector\CallLike\AssertContainsOnlyMethodCallRectorTest
 */
final class AssertContainsOnlyMethodCallRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    /**
     * @var array<string, string>
     */
    private const array TYPE_VALUE_TO_METHOD = ['array' => 'assertContainsOnlyArray', 'bool' => 'assertContainsOnlyBool', 'boolean' => 'assertContainsOnlyBool', 'callable' => 'assertContainsOnlyCallable', 'double' => 'assertContainsOnlyFloat', 'float' => 'assertContainsOnlyFloat', 'int' => 'assertContainsOnlyInt', 'integer' => 'assertContainsOnlyInt', 'iterable' => 'assertContainsOnlyIterable', 'null' => 'assertContainsOnlyNull', 'numeric' => 'assertContainsOnlyNumeric', 'object' => 'assertContainsOnlyObject', 'real' => 'assertContainsOnlyFloat', 'resource' => 'assertContainsOnlyResource', 'resource (closed)' => 'assertContainsOnlyClosedResource', 'scalar' => 'assertContainsOnlyScalar', 'string' => 'assertContainsOnlyString'];
    public function __construct(private readonly ValueResolver $valueResolver, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.5');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replaces `Assert::assertContainsOnly()` calls with type-specific `Assert::assertContainsOnly*()` calls', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeClass extends TestCase
{
    public function testMethod(): void
    {
        $this->assertContainsOnly('string', ['a', 'b']);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeClass extends TestCase
{
    public function testMethod(): void
    {
        $this->assertContainsOnlyString(['a', 'b']);
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
     */
    public function refactor(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!$this->testsNodeAnalyzer->isPHPUnitTestCaseCall($node) || !$this->isName($node->name, 'assertContainsOnly')) {
            return null;
        }
        $typeArg = $node->getArg('type', 0);
        $haystackArg = $node->getArg('haystack', 1);
        if (!$typeArg instanceof Arg || !$haystackArg instanceof Arg) {
            return null;
        }
        $typeValue = $this->valueResolver->getValue($typeArg);
        if (!is_string($typeValue)) {
            return null;
        }
        $newMethodName = self::TYPE_VALUE_TO_METHOD[$typeValue] ?? null;
        if ($newMethodName === null) {
            return null;
        }
        // the $isNativeType argument can turn the type into a class name, that has no type-specific method
        $isNativeTypeArg = $node->getArg('isNativeType', 2);
        if ($isNativeTypeArg instanceof Arg && !$this->valueResolver->isTrue($isNativeTypeArg->value) && !$this->valueResolver->isNull($isNativeTypeArg->value)) {
            return null;
        }
        $newArgs = [new Arg($haystackArg->value)];
        $messageArg = $node->getArg('message', 3);
        if ($messageArg instanceof Arg) {
            $newArgs[] = new Arg($messageArg->value);
        }
        if ($node instanceof MethodCall) {
            return new MethodCall($node->var, $newMethodName, $newArgs);
        }
        return new StaticCall($node->class, $newMethodName, $newArgs);
    }
}
