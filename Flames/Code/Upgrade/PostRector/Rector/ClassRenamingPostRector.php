<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PostRector\Rector;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Flames\Code\Upgrade\Configuration\RenamedClassesDataCollector;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\PostRector\Guard\AddUseStatementGuard;
use Flames\Code\Upgrade\Rules\Renaming\Collector\RenamedNameCollector;
final class ClassRenamingPostRector extends AbstractPostRector
{
    /**
     * @var array<string, string>
     */
    private array $oldToNewClasses = [];
    public function __construct(private readonly RenamedClassesDataCollector $renamedClassesDataCollector, private readonly RenamedNameCollector $renamedNameCollector, private readonly AddUseStatementGuard $addUseStatementGuard)
    {
    }
    /**
     * @return \Flames\Code\Upgrade\PhpParser\Node\FileNode|int
     */
    public function enterNode(Node $node)
    {
        // the FileNode resolves the namespace-or-file placement internally
        if ($node instanceof FileNode) {
            // keep only the uses that were actually renamed
            $removedUses = array_values(array_filter($this->renamedClassesDataCollector->getOldClasses(), \Closure::fromCallable($this->renamedNameCollector->has(...))));
            if ($node->removeImports($removedUses)) {
                $this->addRectorClassWithLine($node);
            }
            $this->renamedNameCollector->reset();
            return $node;
        }
        // nothing else to handle here, as the first node we'll hit is handled above
        return NodeVisitor::STOP_TRAVERSAL;
    }
    public function shouldTraverse(array $stmts): bool
    {
        $this->oldToNewClasses = $this->renamedClassesDataCollector->getOldToNewClasses();
        if ($this->oldToNewClasses === []) {
            return \false;
        }
        return $this->addUseStatementGuard->shouldTraverse($stmts, $this->getFile()->getFilePath());
    }
}
