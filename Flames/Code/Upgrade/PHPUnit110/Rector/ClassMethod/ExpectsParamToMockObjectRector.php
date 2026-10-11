<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit110\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\IntersectionType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\PHPUnit110\Rector\ClassMethod\ExpectsParamToMockObjectRectorTest
 */
final class ExpectsParamToMockObjectRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly BetterNodeFinder $betterNodeFinder, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change param type of a private test method to MockObject, when expects() is called on it', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private function prepareUserMock(SomeUser $user): void
    {
        $user->expects($this->once())
            ->method('getId');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private function prepareUserMock(MockObject $user): void
    {
        $user->expects($this->once())
            ->method('getId');
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
    public function refactor(Node $node): ?ClassMethod
    {
        if (!$node->isPrivate()) {
            return null;
        }
        if ($node->stmts === null || $node->params === []) {
            return null;
        }
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $hasChanged = \false;
        foreach ($node->params as $param) {
            if (!$param->var instanceof Variable) {
                continue;
            }
            $paramName = $this->getName($param->var);
            if ($paramName === null) {
                continue;
            }
            if ($this->hasMockObjectType($param->type)) {
                continue;
            }
            // avoid contradicting the docblock type, that would have to be changed as well
            if ($phpDocInfo->getParamTagValueByName($paramName) instanceof ParamTagValueNode) {
                continue;
            }
            if (!$this->isExpectsCalledOnVariable($node, $paramName)) {
                continue;
            }
            $param->type = new FullyQualified(PHPUnitClassName::MOCK_OBJECT);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @param null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType $type
     */
    private function hasMockObjectType($type): bool
    {
        if ($type instanceof Name) {
            return $this->isName($type, PHPUnitClassName::MOCK_OBJECT);
        }
        if ($type instanceof NullableType) {
            return $this->hasMockObjectType($type->type);
        }
        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            foreach ($type->types as $singleType) {
                if ($this->hasMockObjectType($singleType)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    private function isExpectsCalledOnVariable(ClassMethod $classMethod, string $paramName): bool
    {
        /** @var MethodCall[] $methodCalls */
        $methodCalls = $this->betterNodeFinder->findInstancesOfScoped((array) $classMethod->stmts, MethodCall::class);
        foreach ($methodCalls as $methodCall) {
            if (!$methodCall->var instanceof Variable) {
                continue;
            }
            if (!$this->isName($methodCall->var, $paramName)) {
                continue;
            }
            if ($this->isName($methodCall->name, 'expects')) {
                return \true;
            }
        }
        return \false;
    }
}
