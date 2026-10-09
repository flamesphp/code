<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IntersectionTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareIntersectionTypeNode;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\MockObjectPropertyDetector;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddIntersectionVarToMockObjectPropertyRectorTest
 */
final class AddIntersectionVarToMockObjectPropertyRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly MockObjectPropertyDetector $mockObjectPropertyDetector, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly PhpDocTypeChanger $phpDocTypeChanger)
    {
    }
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Class_
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $setUpClassMethod = $node->getMethod(MethodName::SET_UP);
        if (!$setUpClassMethod instanceof ClassMethod) {
            return null;
        }
        $propertyNamesToCreateMockMethodCalls = $this->mockObjectPropertyDetector->collectFromClassMethod($setUpClassMethod);
        if ($propertyNamesToCreateMockMethodCalls === []) {
            return null;
        }
        $hasChanged = \false;
        foreach ($propertyNamesToCreateMockMethodCalls as $propertyName => $createMockMethodCall) {
            $property = $node->getProperty($propertyName);
            if (!$property instanceof Property) {
                continue;
            }
            // only properties typed as a bare native MockObject
            if (!$this->mockObjectPropertyDetector->detect($property)) {
                continue;
            }
            $mockedClass = $this->resolveMockedClass($createMockMethodCall);
            if ($mockedClass === null) {
                continue;
            }
            $intersectionTypeNode = new BracketsAwareIntersectionTypeNode([new IdentifierTypeNode('\\' . PHPUnitClassName::MOCK_OBJECT), new IdentifierTypeNode('\\' . $mockedClass)]);
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
            // already has an intersection @var, skip
            $varTagValueNode = $phpDocInfo->getVarTagValueNode();
            if ($varTagValueNode instanceof VarTagValueNode && $varTagValueNode->type instanceof IntersectionTypeNode) {
                continue;
            }
            $this->phpDocTypeChanger->changeVarTypeNode($property, $phpDocInfo, $intersectionTypeNode);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add a MockObject intersection @var docblock with the mocked class to a native MockObject property', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $someServiceMock;

    protected function setUp(): void
    {
        $this->someServiceMock = $this->createMock(SomeService::class);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\SomeService
     */
    private \PHPUnit\Framework\MockObject\MockObject $someServiceMock;

    protected function setUp(): void
    {
        $this->someServiceMock = $this->createMock(SomeService::class);
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $createMockCall
     */
    private function resolveMockedClass($createMockCall): ?string
    {
        $firstArg = $createMockCall->getArgs()[0] ?? null;
        if ($firstArg === null) {
            return null;
        }
        if (!$firstArg->value instanceof ClassConstFetch) {
            return null;
        }
        $className = $this->getName($firstArg->value->class);
        if (!is_string($className)) {
            return null;
        }
        return $className;
    }
}
