<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Testing\TestingParser;

use Flames\Code\Upgrade\ThirdParty\Nette\FileSystem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeTraverser;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor\NameResolver;
use Flames\Code\Upgrade\Application\Provider\CurrentFileProvider;
use Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\UsedImportsResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeScopeAndMetadataDecorator;
use Flames\Code\Upgrade\NodeTypeResolver\Reflection\BetterReflection\SourceLocatorProvider\DynamicSourceLocatorProvider;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\PhpParser\Parser\RectorParser;
use Flames\Code\Upgrade\ValueObject\Application\File;
/**
 * @api
 */
final readonly class TestingParser
{
    public function __construct(private RectorParser $rectorParser, private NodeScopeAndMetadataDecorator $nodeScopeAndMetadataDecorator, private CurrentFileProvider $currentFileProvider, private DynamicSourceLocatorProvider $dynamicSourceLocatorProvider, private UsedImportsResolver $usedImportsResolver)
    {
    }
    public function parseFilePathToFile(string $filePath): File
    {
        [$file, $stmts] = $this->parseToFileAndStmts($filePath);
        return $file;
    }
    /**
     * @return Node[]
     */
    public function parseFileToDecoratedNodes(string $filePath): array
    {
        [$file, $stmts] = $this->parseToFileAndStmts($filePath);
        return $stmts;
    }
    /**
     * @return array{0: File, 1: Node[]}
     */
    private function parseToFileAndStmts(string $filePath): array
    {
        // needed for PHPStan reflection, as it caches the last processed file
        $this->dynamicSourceLocatorProvider->setFilePath($filePath);
        $fileContent = FileSystem::read($filePath);
        $file = new File($filePath, $fileContent);
        $stmts = $this->rectorParser->parseString($fileContent);
        // resolve names up front, so used imports are resolvable at construction, before decoration;
        // only annotates namespacedName, does not replace name nodes
        $nameResolvingNodeTraverser = new NodeTraverser(new NameResolver(null, ['preserveOriginalNames' => \true, 'replaceNodes' => \false]));
        $stmts = $nameResolvingNodeTraverser->traverse($stmts);
        // wrap in FileNode to enable file-level rules; seed used imports once, kept in sync incrementally
        $stmts = [new FileNode($stmts, $this->usedImportsResolver->resolveForStmts($stmts))];
        $stmts = $this->nodeScopeAndMetadataDecorator->decorateNodesFromFile($filePath, $stmts);
        $file->hydrateStmtsAndTokens($stmts, $stmts, []);
        $this->currentFileProvider->setFile($file);
        return [$file, $stmts];
    }
}
