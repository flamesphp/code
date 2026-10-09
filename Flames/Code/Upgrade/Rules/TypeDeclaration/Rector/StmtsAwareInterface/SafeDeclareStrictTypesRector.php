<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\StmtsAwareInterface;

use PhpParser\Node;
use PhpParser\Node\Stmt\Nop;
use Flames\Code\Upgrade\Contract\Rector\HTMLAverseRectorInterface;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\DeclareStrictTypeFinder;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\StrictTypeSafetyChecker;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRectorTest
 */
final class SafeDeclareStrictTypesRector extends AbstractRector implements HTMLAverseRectorInterface, MinPhpVersionInterface
{
    public function __construct(private readonly DeclareStrictTypeFinder $declareStrictTypeFinder, private readonly StrictTypeSafetyChecker $strictTypeSafetyChecker)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add `declare(strict_types=1)` if missing and only if the file is type-safe (no scalar type coercions).', [new CodeSample(<<<'CODE_SAMPLE'
function acceptsInt(int $value): void
{
}

acceptsInt(5);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
declare(strict_types=1);

function acceptsInt(int $value): void
{
}

acceptsInt(5);
CODE_SAMPLE
)]);
    }
    /**
     * @param FileNode $node
     */
    public function refactor(Node $node): ?FileNode
    {
        if ($this->declareStrictTypeFinder->hasDeclareStrictTypes($node)) {
            return null;
        }
        if (!$this->strictTypeSafetyChecker->isFileStrictTypeSafe($node)) {
            return null;
        }
        $declaresStrictType = $this->nodeFactory->createDeclaresStrictType();
        $node->stmts = array_merge([$declaresStrictType, new Nop()], $node->stmts);
        return $node;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FileNode::class];
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_70;
    }
}
