<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\NodeTypeResolver\PhpDoc\NodeAnalyzer\DocBlockTagReplacer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Renaming\Contract\RenameAnnotationInterface;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameAnnotationByType;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Renaming\Rector\ClassMethod\RenameAnnotationRectorTest
 */
final class RenameAnnotationRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var RenameAnnotationInterface[]
     */
    private array $renameAnnotations = [];
    public function __construct(private readonly DocBlockTagReplacer $docBlockTagReplacer, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turn defined annotations above properties and methods to their new values', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @test
     */
    public function someMethod()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @scenario
     */
    public function someMethod()
    {
    }
}
CODE_SAMPLE
, [new RenameAnnotationByType(\PHPUnit\Framework\TestCase::class, 'test', 'scenario')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Expression::class];
    }
    /**
     * @param Class_|Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Expression) {
            return $this->refactorExpression($node);
        }
        $hasChanged = \false;
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassMethod && !$stmt instanceof Property) {
                continue;
            }
            $phpDocInfo = $this->phpDocInfoFactory->createFromNode($stmt);
            if (!$phpDocInfo instanceof PhpDocInfo) {
                continue;
            }
            foreach ($this->renameAnnotations as $renameAnnotation) {
                if ($renameAnnotation instanceof RenameAnnotationByType && !$this->isObjectType($node, $renameAnnotation->getObjectType())) {
                    continue;
                }
                $hasDocBlockChanged = $this->docBlockTagReplacer->replaceTagByAnother($phpDocInfo, $renameAnnotation->getOldAnnotation(), $renameAnnotation->getNewAnnotation());
                if ($hasDocBlockChanged) {
                    $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($stmt);
                    $hasChanged = \true;
                }
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, RenameAnnotationInterface::class);
        $this->renameAnnotations = $configuration;
    }
    private function refactorExpression(Expression $expression): ?Expression
    {
        $hasChanged = \false;
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($expression);
        foreach ($this->renameAnnotations as $renameAnnotation) {
            $hasDocBlockChanged = $this->docBlockTagReplacer->replaceTagByAnother($phpDocInfo, $renameAnnotation->getOldAnnotation(), $renameAnnotation->getNewAnnotation());
            if ($hasDocBlockChanged) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($expression);
            return $expression;
        }
        return null;
    }
}
