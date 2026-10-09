<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Comment\Doc;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Util\NewLineSplitter;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use function in_array;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\ReplaceTestAnnotationWithPrefixedFunctionRectorTest
 */
final class ReplaceTestAnnotationWithPrefixedFunctionRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly PhpDocTagRemover $phpDocTagRemover, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace @test with prefixed function', [new CodeSample(<<<'CODE_SAMPLE'
class SomeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function onePlusOneShouldBeTwo()
    {
        $this->assertSame(2, 1+1);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeTest extends \PHPUnit\Framework\TestCase
{
    public function testOnePlusOneShouldBeTwo()
    {
        $this->assertSame(2, 1+1);
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
        if (str_starts_with($node->name->toString(), 'test')) {
            return null;
        }
        $docComment = $node->getDocComment();
        if (!$docComment instanceof Doc) {
            return null;
        }
        $hasAnnotation = array_any(NewLineSplitter::split($docComment->getText()), fn($row) => in_array(trim($row), ['*@test', '* @test'], \true));
        if (!$hasAnnotation) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $this->phpDocTagRemover->removeByName($phpDocInfo, 'test');
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        $node->name->name = 'test' . ucfirst($node->name->name);
        return $node;
    }
}
