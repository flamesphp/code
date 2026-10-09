<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit120\Rector\Class_;

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
 * The is*() methods were added and isType() deprecated in PHPUnit 11.5
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/6052
 * @see https://github.com/sebastianbergmann/phpunit/blob/11.5.0/ChangeLog-11.5.md
 *
 * @see \Flames\Code\Upgrade\PHPUnit120\Rector\Class_\AssertIsTypeMethodCallRectorTest
 */
final class AssertIsTypeMethodCallRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    /**
     * @var array<string, string>
     */
    private const array IS_TYPE_VALUE_TO_METHOD = ['array' => 'isArray', 'bool' => 'isBool', 'boolean' => 'isBool', 'callable' => 'isCallable', 'double' => 'isFloat', 'float' => 'isFloat', 'integer' => 'isInt', 'int' => 'isInt', 'iterable' => 'isIterable', 'null' => 'isNull', 'numeric' => 'isNumeric', 'object' => 'isObject', 'real' => 'isFloat', 'resource' => 'isResource', 'resource (closed)' => 'isClosedResource', 'scalar' => 'isScalar', 'string' => 'isString'];
    public function __construct(private readonly ValueResolver $valueResolver, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.5');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replaces `Assert::isType()` calls with type-specific `Assert::is*()` calls', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeClass extends TestCase
{
    public function testMethod(): void
    {
        $this->assertThat([], $this->isType('array'));
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeClass extends TestCase
{
    public function testMethod(): void
    {
        $this->assertThat([], $this->isArray());
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
        if (!$this->testsNodeAnalyzer->isPHPUnitTestCaseCall($node) || !$this->isName($node->name, 'isType')) {
            return null;
        }
        if (count($node->getArgs()) !== 1) {
            return null;
        }
        $arg = $node->getArg('type', 0);
        if (!$arg instanceof Arg) {
            return null;
        }
        $argValue = $this->valueResolver->getValue($arg);
        if (!is_string($argValue)) {
            return null;
        }
        if (isset(self::IS_TYPE_VALUE_TO_METHOD[$argValue])) {
            if ($node instanceof MethodCall) {
                return new MethodCall($node->var, self::IS_TYPE_VALUE_TO_METHOD[$argValue]);
            }
            return new StaticCall($node->class, self::IS_TYPE_VALUE_TO_METHOD[$argValue]);
        }
        return null;
    }
}
