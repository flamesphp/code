<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit60\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Nop;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\GenericTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitAttribute;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\AssertCallAnalyzer;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\MockedVariableAnalyzer;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://phpunit.readthedocs.io/en/9.5/annotations.html#doesnotperformassertions
 * @changelog https://github.com/sebastianbergmann/phpunit/issues/2484
 *
 * @see \Flames\Code\Upgrade\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRectorTest
 */
final class AddDoesNotPerformAssertionToNonAssertingTestRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    /**
     * inherited from the PHPUnit 6.0 set
     */
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=6.0');
    }
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly AssertCallAnalyzer $assertCallAnalyzer, private readonly MockedVariableAnalyzer $mockedVariableAnalyzer, private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ReflectionResolver $reflectionResolver, private readonly ReflectionProvider $reflectionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Tests without assertion will have #[DoesNotPerformAssertions] attribute, or @doesNotPerformAssertions annotation on PHPUnit below 10', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeClass extends TestCase
{
    public function test()
    {
        $nothing = 5;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeClass extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function test()
    {
        $nothing = 5;
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkipClassMethod($node)) {
            return null;
        }
        $this->removeAddToAssertionCountCalls($node);
        // the attribute is available since PHPUnit 10, prefer it over the annotation
        if ($this->reflectionProvider->hasClass(PHPUnitAttribute::DOES_NOT_PERFORM_ASSERTIONS)) {
            $node->attrGroups[] = new AttributeGroup([new Attribute(new FullyQualified(PHPUnitAttribute::DOES_NOT_PERFORM_ASSERTIONS))]);
            return $node;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $phpDocInfo->addPhpDocTagNode(new PhpDocTagNode('@doesNotPerformAssertions', new GenericTagValueNode('')));
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    private function shouldSkipClassMethod(ClassMethod $classMethod): bool
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($classMethod)) {
            return \true;
        }
        if (!$this->testsNodeAnalyzer->isTestClassMethod($classMethod)) {
            return \true;
        }
        if ($classMethod->isAbstract()) {
            return \true;
        }
        // we have no idea how the trait is used, the using class can assert on its own
        if ($this->isInTrait($classMethod)) {
            return \true;
        }
        // the parent test case asserts in its own integration methods
        if ($this->isInTwigIntegrationTestCase($classMethod)) {
            return \true;
        }
        if ($this->hasAssertingAnnotationOrAttribute($classMethod)) {
            return \true;
        }
        $this->assertCallAnalyzer->resetNesting();
        if ($this->assertCallAnalyzer->containsAssertCall($classMethod)) {
            return \true;
        }
        return $this->mockedVariableAnalyzer->containsMockAsUsedVariable($classMethod);
    }
    /**
     * The assertion count fakes an assertion, but the "@doesNotPerformAssertions" annotation makes it obsolete
     */
    private function removeAddToAssertionCountCalls(ClassMethod $classMethod): void
    {
        $hasJustRemovedCall = \false;
        $this->traverseNodesWithCallable($classMethod, function (Node $node) use (&$hasJustRemovedCall): ?int {
            // a comment on the same line as the removed call is parsed as a nop statement right behind it
            if ($hasJustRemovedCall && $node instanceof Nop) {
                return NodeVisitor::REMOVE_NODE;
            }
            $hasJustRemovedCall = \false;
            if (!$this->isAddToAssertionCountExpression($node)) {
                return null;
            }
            $hasJustRemovedCall = \true;
            return NodeVisitor::REMOVE_NODE;
        });
    }
    private function isAddToAssertionCountExpression(Node $node): bool
    {
        if (!$node instanceof Expression) {
            return \false;
        }
        if (!$node->expr instanceof MethodCall) {
            return \false;
        }
        $methodCall = $node->expr;
        if (!$this->isName($methodCall->var, 'this')) {
            return \false;
        }
        return $this->isName($methodCall->name, 'addToAssertionCount');
    }
    private function isInTrait(ClassMethod $classMethod): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($classMethod);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        return $classReflection->isTrait();
    }
    private function isInTwigIntegrationTestCase(ClassMethod $classMethod): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($classMethod);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        return $classReflection->is(PHPUnitClassName::TWIG_INTEGRATION_TEST_CASE);
    }
    private function hasAssertingAnnotationOrAttribute(ClassMethod $classMethod): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
        if ($phpDocInfo->hasByNames(['doesNotPerformAssertions', 'expectedException'])) {
            return \true;
        }
        return $this->phpAttributeAnalyzer->hasPhpAttribute($classMethod, PHPUnitAttribute::DOES_NOT_PERFORM_ASSERTIONS);
    }
}
