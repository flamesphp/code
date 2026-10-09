<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\StmtsAwareInterface;

use PhpParser\Node;
use PhpParser\Node\Stmt\Nop;
use Flames\Code\Upgrade\Contract\Rector\HTMLAverseRectorInterface;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\DeclareStrictTypeFinder;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRectorTest
 */
final class DeclareStrictTypesRector extends AbstractRector implements HTMLAverseRectorInterface, MinPhpVersionInterface
{
    public function __construct(private readonly DeclareStrictTypeFinder $declareStrictTypeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add `declare(strict_types=1)` if missing in a namespaced file', [new CodeSample(<<<'CODE_SAMPLE'
namespace App;

class SomeClass
{
    function someFunction(int $number)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
declare(strict_types=1);

namespace App;

class SomeClass
{
    function someFunction(int $number)
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param FileNode $node
     */
    public function refactor(Node $node): ?FileNode
    {
        // shebang files cannot have declare strict types
        if ($this->getFile()->hasShebang()) {
            return null;
        }
        // only add to namespaced files, as global namespace files are often included in other files
        if (!$node->isNamespaced()) {
            return null;
        }
        // when first stmt is Declare_, verify if there is strict_types definition already,
        // as multiple declare is allowed, with declare(strict_types=1) only allowed on very first stmt
        if ($this->declareStrictTypeFinder->hasDeclareStrictTypes($node)) {
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
