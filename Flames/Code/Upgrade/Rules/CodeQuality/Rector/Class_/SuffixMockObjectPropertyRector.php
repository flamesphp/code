<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VarLikeIdentifier;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\MockObjectPropertyDetector;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\SuffixMockObjectPropertyRectorTest
 */
final class SuffixMockObjectPropertyRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly MockObjectPropertyDetector $mockObjectPropertyDetector)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Suffix mock object property names with "Mock" to clearly separate from real objects later on', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class MockingEntity extends TestCase
{
    private MockObject $simpleObject;

    protected function setUp(): void
    {
        $this->simpleObject = $this->createMock(SimpleObject::class);
    }

    public function test()
    {
        $this->simpleObject->method('someMethod')->willReturn('someValue');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class MockingEntity extends TestCase
{
    private MockObject $simpleObjectMock;

    protected function setUp(): void
    {
        $this->simpleObjectMock = $this->createMock(SimpleObject::class);
    }

    public function test()
    {
        $this->simpleObjectMock->method('someMethod')->willReturn('someValue');
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
    public function refactor(Node $node): ?Class_
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            if (!$this->mockObjectPropertyDetector->detect($property)) {
                continue;
            }
            $propertyName = $this->getName($property);
            if (str_ends_with($propertyName, 'Mock')) {
                continue;
            }
            $newPropertyName = $propertyName . 'Mock';
            $property->props[0]->name = new VarLikeIdentifier($newPropertyName);
            $this->renamePropertyUsagesInClass($node, $propertyName, $newPropertyName);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function renamePropertyUsagesInClass(Class_ $class, string $oldPropertyName, string $newPropertyName): void
    {
        $this->traverseNodesWithCallable($class, function (Node $node) use ($oldPropertyName, $newPropertyName): ?Node {
            if (!$node instanceof PropertyFetch) {
                return null;
            }
            // is local property?
            if (!$node->var instanceof Variable && !$this->isName($node->var, 'this')) {
                return null;
            }
            if (!$this->isName($node->name, $oldPropertyName)) {
                return null;
            }
            $node->name = new Identifier($newPropertyName);
            return $node;
        });
    }
}
