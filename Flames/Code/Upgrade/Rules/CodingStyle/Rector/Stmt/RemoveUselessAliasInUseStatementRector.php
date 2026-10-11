<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\Stmt;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\Stmt\RemoveUselessAliasInUseStatementRectorTest
 */
final class RemoveUselessAliasInUseStatementRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove useless alias in use statement as same name with last use statement name', [new CodeSample(<<<'CODE_SAMPLE'
use App\Bar as Bar;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use App\Bar;
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FileNode::class, Namespace_::class];
    }
    /**
     * @param Namespace_|FileNode $node
     * @return null|\Flames\Code\Upgrade\PhpParser\Node\FileNode|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_
     */
    public function refactor(Node $node)
    {
        if ($node instanceof FileNode && $node->isNamespaced()) {
            // handle in Namespace_ node
            return null;
        }
        $hasChanged = \false;
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Use_) {
                continue;
            }
            if (count($stmt->uses) !== 1) {
                continue;
            }
            if (!isset($stmt->uses[0])) {
                continue;
            }
            $aliasName = $stmt->uses[0]->alias instanceof Identifier ? $stmt->uses[0]->alias->toString() : null;
            if ($aliasName === null) {
                continue;
            }
            $useName = $stmt->uses[0]->name->toString();
            $lastName = Strings::after($useName, '\\', -1) ?? $useName;
            if ($lastName === $aliasName) {
                $stmt->uses[0]->alias = null;
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
