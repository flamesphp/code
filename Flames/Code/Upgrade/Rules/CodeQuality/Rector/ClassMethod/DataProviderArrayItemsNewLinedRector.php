<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitAttribute;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\DataProviderArrayItemsNewLinedRectorTest
 */
final class DataProviderArrayItemsNewLinedRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly BetterNodeFinder $betterNodeFinder, private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change data provider in PHPUnit test case to newline per item', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class ImageBinaryTest extends TestCase
{
    /**
     * @dataProvider provideData()
     */
    public function testGetBytesSize(string $content, int $number): void
    {
        // ...
    }

    public static function provideData(): array
    {
        return [['content', 8], ['content123', 11]];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class ImageBinaryTest extends TestCase
{
    /**
     * @dataProvider provideData()
     */
    public function testGetBytesSize(string $content, int $number): void
    {
        // ...
    }

    public static function provideData(): array
    {
        return [
            ['content', 8],
            ['content123', 11]
        ];
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
        if (!$node->isPublic()) {
            return null;
        }
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        // skip test methods
        if (str_starts_with($node->name->toString(), 'test') || $this->phpAttributeAnalyzer->hasPhpAttribute($node, PHPUnitAttribute::TEST)) {
            return null;
        }
        // find array in data provider - must contain a return node
        /** @var Return_[] $returns */
        $returns = $this->betterNodeFinder->findInstanceOf((array) $node->stmts, Return_::class);
        $hasChanged = \false;
        foreach ($returns as $return) {
            if (!$return->expr instanceof Array_) {
                continue;
            }
            $array = $return->expr;
            if ($array->items === []) {
                continue;
            }
            if (!$this->shouldRePrint($array)) {
                continue;
            }
            // ensure newlined printed
            $array->setAttribute(AttributeKey::NEWLINED_ARRAY_PRINT, \true);
            // invoke reprint
            $array->setAttribute(AttributeKey::ORIGINAL_NODE, null);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function shouldRePrint(Array_ $array): bool
    {
        foreach ($array->items as $key => $item) {
            if (!isset($array->items[$key + 1])) {
                continue;
            }
            if ($array->items[$key + 1]->getStartLine() !== $item->getEndLine()) {
                continue;
            }
            return \true;
        }
        return \false;
    }
}
