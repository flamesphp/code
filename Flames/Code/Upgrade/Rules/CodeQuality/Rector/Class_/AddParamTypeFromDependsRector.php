<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\PHPUnit\Framework\Attributes\Depends;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer\AttrinationFinder;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddParamTypeFromDependsRectorTest
 */
final class AddParamTypeFromDependsRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly AttrinationFinder $attrinationFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add param type declaration based on @depends test method return type', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test(): \stdClass
    {
        return new \stdClass();
    }

    /**
     * @depends test
     */
    public function testAnother($someObject)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test(): \stdClass
    {
        return new \stdClass();
    }

    /**
     * @depends test
     */
    public function testAnother(\stdClass $someObject)
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
            if (count($classMethod->params) !== 1) {
                continue;
            }
            $soleParam = $classMethod->getParams()[0];
            // already known type
            if ($soleParam->type instanceof Node) {
                continue;
            }
            $dependsReturnType = $this->resolveReturnTypeOfDependsMethod($classMethod, $node);
            if (!$dependsReturnType instanceof Node) {
                continue;
            }
            $soleParam->type = $dependsReturnType;
            $hasChanged = \true;
        }
        if ($hasChanged === \false) {
            return null;
        }
        return $node;
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name|null
     */
    private function resolveReturnTypeOfDependsMethod(ClassMethod $classMethod, Class_ $class)
    {
        $dependsMethodName = $this->resolveDependsAnnotationOrAttributeMethod($classMethod);
        if ($dependsMethodName === null || $dependsMethodName === '') {
            return null;
        }
        $dependsClassMethod = $class->getMethod($dependsMethodName);
        if (!$dependsClassMethod instanceof ClassMethod) {
            return null;
        }
        // resolve return type here
        return $dependsClassMethod->returnType;
    }
    private function resolveDependsAnnotationOrAttributeMethod(ClassMethod $classMethod): ?string
    {
        $dependsAttribute = $this->attrinationFinder->getByOne($classMethod, Depends::class);
        if ($dependsAttribute instanceof Attribute) {
            $firstArg = $dependsAttribute->args[0];
            if ($firstArg->value instanceof String_) {
                $dependsMethodName = $firstArg->value->value;
                return trim($dependsMethodName, '()');
            }
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($classMethod);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $dependsTagValueNode = $phpDocInfo->getByName('depends');
        if (!$dependsTagValueNode instanceof PhpDocTagNode) {
            return null;
        }
        $dependsMethodName = (string) $dependsTagValueNode->value;
        return trim($dependsMethodName, '()');
    }
}
