<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\Nette\Json;
use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\GenericTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\NodeManipulator\ClassInsertManipulator;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\TestWithToDataProviderRectorTest
 */
final class TestWithToDataProviderRector extends AbstractRector
{
    private bool $hasChanged = \false;
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly PhpDocTagRemover $phpDocTagRemover, private readonly DocBlockUpdater $docBlockUpdater, private readonly ClassInsertManipulator $classInsertManipulator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace testWith annotation to data provider.', [new CodeSample(<<<'CODE_SAMPLE'
/**
 * @testWith    [0, 0, 0]
 * @testWith    [0, 1, 1]
 * @testWith    [1, 0, 1]
 * @testWith    [1, 1, 3]
 */
public function testSum(int $a, int $b, int $expected)
{
    $this->assertSame($expected, $a + $b);
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
public function dataProviderSum()
{
    return [
        [0, 0, 0],
        [0, 1, 1],
        [1, 0, 1],
        [1, 1, 3]
    ];
}

/**
 * @dataProvider dataProviderSum
 */
public function test(int $a, int $b, int $expected)
{
    $this->assertSame($expected, $a + $b);
}
CODE_SAMPLE
)]);
    }
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
        $this->hasChanged = \false;
        foreach ($node->stmts as $classMethod) {
            if (!$classMethod instanceof ClassMethod) {
                continue;
            }
            $this->refactorClassMethod($node, $classMethod);
        }
        if (!$this->hasChanged) {
            return null;
        }
        return $node;
    }
    private function refactorClassMethod(Class_ $class, ClassMethod $classMethod): void
    {
        $arrayItemsSingleLine = [];
        $arrayMultiLine = null;
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($classMethod);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return;
        }
        $testWithPhpDocTagNodes = array_merge($phpDocInfo->getTagsByName('testWith'), $phpDocInfo->getTagsByName('testwith'));
        if ($testWithPhpDocTagNodes === []) {
            return;
        }
        foreach ($testWithPhpDocTagNodes as $testWithPhpDocTagNode) {
            if (!$testWithPhpDocTagNode->value instanceof GenericTagValueNode) {
                continue;
            }
            $values = $this->extractTestWithData($testWithPhpDocTagNode->value);
            if (count($values) > 1) {
                $arrayMultiLine = $this->createArrayItem($values);
            }
            if (count($values) === 1) {
                $arrayItemsSingleLine[] = new ArrayItem($this->createArrayItem($values[0]));
            }
            // cleanup
            if ($this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $testWithPhpDocTagNode)) {
                $this->hasChanged = \true;
            }
        }
        if (!$this->hasChanged) {
            return;
        }
        $dataProviderName = $this->generateDataProviderName($classMethod);
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
        $phpDocInfo->addPhpDocTagNode(new PhpDocTagNode('@dataProvider', new GenericTagValueNode($dataProviderName)));
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($classMethod);
        $returnValue = $arrayMultiLine;
        if ($arrayItemsSingleLine !== []) {
            $returnValue = new Array_($arrayItemsSingleLine);
        }
        $providerMethod = new ClassMethod($dataProviderName);
        $providerMethod->flags = Modifiers::PUBLIC;
        $providerMethod->stmts[] = new Return_($returnValue);
        $this->classInsertManipulator->addAsFirstMethod($class, $providerMethod);
    }
    /**
     * @return array<array<int, mixed>>
     */
    private function extractTestWithData(GenericTagValueNode $genericTagValueNode): array
    {
        $testWithItems = explode("\n", trim($genericTagValueNode->value));
        $jsonArray = [];
        foreach ($testWithItems as $testWithItem) {
            $jsonArray[] = Json::decode(trim($testWithItem), \true);
        }
        return $jsonArray;
    }
    /**
     * @param array<int|string, mixed> $data
     */
    private function createArrayItem(array $data): Array_
    {
        $values = [];
        foreach ($data as $index => $item) {
            $key = null;
            if (is_string($index)) {
                $key = new String_($index);
            }
            $values[] = new ArrayItem($this->parseArrayItemValue($item), $key);
        }
        return new Array_($values);
    }
    /**
     * @param mixed $value
     */
    private function parseArrayItemValue($value): Expr
    {
        if (is_array($value)) {
            return $this->createArrayItem($value);
        }
        $name = new Name(Json::encode($value));
        return new ConstFetch($name);
    }
    private function generateDataProviderName(ClassMethod $classMethod): string
    {
        $methodName = Strings::replace($classMethod->name->name, '/^test/', '');
        $upperCaseFirstLatter = ucfirst($methodName);
        return sprintf('%s%s', 'dataProvider', $upperCaseFirstLatter);
    }
}
