<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PostRector\Application;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeTraverser;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\Configuration\RenamedClassesDataCollector;
use Flames\Code\Upgrade\Contract\DependencyInjection\ResettableInterface;
use Flames\Code\Upgrade\PostRector\Contract\Rector\PostRectorInterface;
use Flames\Code\Upgrade\PostRector\Rector\ClassRenamingPostRector;
use Flames\Code\Upgrade\PostRector\Rector\DocblockNameImportingPostRector;
use Flames\Code\Upgrade\PostRector\Rector\NameImportingPostRector;
use Flames\Code\Upgrade\PostRector\Rector\UnusedImportRemovingPostRector;
use Flames\Code\Upgrade\PostRector\Rector\UseAddingPostRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\Name\RenameClassRector;
use Flames\Code\Upgrade\Skipper\Skipper\Skipper;
use Flames\Code\Upgrade\ValueObject\Application\File;
final class PostFileProcessor implements ResettableInterface
{
    /**
     * @var PostRectorInterface[]
     */
    private array $postRectors = [];
    public function __construct(private readonly Skipper $skipper, private readonly UseAddingPostRector $useAddingPostRector, private readonly NameImportingPostRector $nameImportingPostRector, private readonly ClassRenamingPostRector $classRenamingPostRector, private readonly DocblockNameImportingPostRector $docblockNameImportingPostRector, private readonly UnusedImportRemovingPostRector $unusedImportRemovingPostRector, private readonly RenamedClassesDataCollector $renamedClassesDataCollector)
    {
    }
    public function reset(): void
    {
        $this->postRectors = [];
    }
    /**
     * @param Stmt[] $stmts
     * @return Stmt[]
     */
    public function traverse(array $stmts, File $file): array
    {
        foreach ($this->getPostRectors() as $postRector) {
            $postRector->setFile($file);
            if ($this->shouldSkipPostRector($postRector, $file->getFilePath(), $stmts)) {
                continue;
            }
            $nodeTraverser = new NodeTraverser($postRector);
            $stmts = $nodeTraverser->traverse($stmts);
        }
        return $stmts;
    }
    /**
     * @param Stmt[] $stmts
     */
    private function shouldSkipPostRector(PostRectorInterface $postRector, string $filePath, array $stmts): bool
    {
        if ($this->skipper->shouldSkipElementAndFilePath($postRector, $filePath)) {
            return \true;
        }
        if ($postRector instanceof ClassRenamingPostRector && $this->skipper->shouldSkipElementAndFilePath(RenameClassRector::class, $filePath)) {
            return \true;
        }
        return !$postRector->shouldTraverse($stmts);
    }
    /**
     * Lazy load, to enable test reset with different configuration
     * @return PostRectorInterface[]
     */
    private function getPostRectors(): array
    {
        if ($this->postRectors !== []) {
            return $this->postRectors;
        }
        $isRenamedClassEnabled = $this->renamedClassesDataCollector->getOldToNewClasses() !== [];
        $isNameImportingEnabled = SimpleParameterProvider::provideBoolParameter(Option::AUTO_IMPORT_NAMES);
        $isRemovingUnusedImportsEnabled = SimpleParameterProvider::provideBoolParameter(Option::REMOVE_UNUSED_IMPORTS);
        $postRectors = [];
        if ($isRenamedClassEnabled && $isNameImportingEnabled) {
            $postRectors[] = $this->classRenamingPostRector;
        }
        if ($isNameImportingEnabled) {
            $postRectors[] = $this->nameImportingPostRector;
            if (SimpleParameterProvider::provideBoolParameter(Option::AUTO_IMPORT_DOC_BLOCK_NAMES)) {
                $postRectors[] = $this->docblockNameImportingPostRector;
            }
        }
        $postRectors[] = $this->useAddingPostRector;
        if ($isRemovingUnusedImportsEnabled) {
            $postRectors[] = $this->unusedImportRemovingPostRector;
        }
        $this->postRectors = $postRectors;
        return $this->postRectors;
    }
}
