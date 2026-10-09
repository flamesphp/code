<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer\AttributeFinder;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\ParentCallDetector;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\NoSetupWithParentCallOverrideRectorTest
 */
final class NoSetupWithParentCallOverrideRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly ParentCallDetector $parentCallDetector, private readonly AttributeFinder $attributeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove override attribute, if setUp()/tearDown() references parent call to improve readability', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $value = 100;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $value = 100;
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
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        if (!$this->isNames($node, ['setUp', 'tearDown'])) {
            return null;
        }
        if (!$this->parentCallDetector->hasParentCall($node)) {
            return null;
        }
        if (!$this->attributeFinder->hasAttributeByClasses($node, ['Override'])) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->attrGroups as $attributeGroupKey => $attrGroup) {
            foreach ($attrGroup->attrs as $attributeKey => $attribute) {
                if (!$this->isName($attribute->name, 'Override')) {
                    continue;
                }
                unset($attrGroup->attrs[$attributeKey]);
                $hasChanged = \true;
            }
            if ($attrGroup->attrs === []) {
                unset($node->attrGroups[$attributeGroupKey]);
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
}
