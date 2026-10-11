<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Application;

use Flames\Code\Upgrade\ThirdParty\Nette\FileSystem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeTraverser;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor\NameResolver;
use PHPStan\AnalysedCodeException;
use PHPStan\Parser\ParserErrorsException;
use Flames\Code\Upgrade\Caching\Detector\ChangedFilesDetector;
use Flames\Code\Upgrade\ChangesReporting\ValueObjectFactory\ErrorFactory;
use Flames\Code\Upgrade\ChangesReporting\ValueObjectFactory\FileDiffFactory;
use Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\UsedImportsResolver;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\FileSystem\FilePathHelper;
use Flames\Code\Upgrade\NodeTypeResolver\NodeScopeAndMetadataDecorator;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\PhpParser\NodeTraverser\RectorNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Parser\ParserErrors;
use Flames\Code\Upgrade\PhpParser\Parser\RectorParser;
use Flames\Code\Upgrade\PhpParser\Printer\BetterStandardPrinter;
use Flames\Code\Upgrade\PostRector\Application\PostFileProcessor;
use Flames\Code\Upgrade\Testing\PHPUnit\StaticPHPUnitEnvironment;
use Flames\Code\Upgrade\ValueObject\Application\File;
use Flames\Code\Upgrade\ValueObject\Configuration;
use Flames\Code\Upgrade\ValueObject\Error\SystemError;
use Flames\Code\Upgrade\ValueObject\FileProcessResult;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
final readonly class FileProcessor
{
    public function __construct(private BetterStandardPrinter $betterStandardPrinter, private RectorNodeTraverser $rectorNodeTraverser, private SymfonyStyle $symfonyStyle, private FileDiffFactory $fileDiffFactory, private ChangedFilesDetector $changedFilesDetector, private ErrorFactory $errorFactory, private FilePathHelper $filePathHelper, private PostFileProcessor $postFileProcessor, private RectorParser $rectorParser, private NodeScopeAndMetadataDecorator $nodeScopeAndMetadataDecorator, private UsedImportsResolver $usedImportsResolver)
    {
    }
    public function processFile(File $file, Configuration $configuration): FileProcessResult
    {
        // 1. parse files to nodes
        $parsingSystemError = $this->parseFileAndDecorateNodes($file);
        if ($parsingSystemError instanceof SystemError) {
            // we cannot process this file as the parsing and type resolving itself went wrong
            return new FileProcessResult([$parsingSystemError], null, \false);
        }
        $fileHasChanged = \false;
        $filePath = $file->getFilePath();
        do {
            $file->changeHasChanged(\false);
            // 1. change nodes with Upgrade Rules
            $newStmts = $this->rectorNodeTraverser->traverse($file->getNewStmts());
            // 2. apply post rectors
            $postNewStmts = $this->postFileProcessor->traverse($newStmts, $file);
            // 3. this is needed for new tokens added in "afterTraverse()"
            $file->changeNewStmts($postNewStmts);
            // 4. print to file or string
            // important to detect if file has changed
            $this->printFile($file, $configuration, $filePath);
            // no change in current iteration, stop
            if (!$file->hasChanged()) {
                break;
            }
            $fileHasChanged = \true;
        } while (\true);
        // 5. add as cacheable if not changed at all
        if (!$fileHasChanged) {
            $this->changedFilesDetector->addCacheableFile($filePath);
        } else {
            // when changed, set final status changed to true
            // to ensure it make sense to verify in next process when needed
            $file->changeHasChanged(\true);
        }
        $rectorWithLineChanges = $file->getUpgradeWithLineChanges();
        if ($file->hasChanged() || $rectorWithLineChanges !== []) {
            $currentFileDiff = $this->fileDiffFactory->createFileDiffWithLineChanges($configuration->shouldShowDiffs(), $file, $file->getOriginalFileContent(), $file->getFileContent(), $file->getUpgradeWithLineChanges());
            $file->setFileDiff($currentFileDiff);
        }
        return new FileProcessResult([], $file->getFileDiff(), $file->hasChanged());
    }
    private function parseFileAndDecorateNodes(File $file): ?SystemError
    {
        try {
            try {
                $this->parseFileNodes($file);
            } catch (ParserErrorsException) {
                $this->parseFileNodes($file, \false);
            }
        } catch (ShouldNotHappenException $shouldNotHappenException) {
            throw $shouldNotHappenException;
        } catch (AnalysedCodeException $analysedCodeException) {
            // inform about missing classes in tests
            if (StaticPHPUnitEnvironment::isPHPUnitRun()) {
                throw $analysedCodeException;
            }
            return $this->errorFactory->createAutoloadError($analysedCodeException, $file->getFilePath());
        } catch (Throwable $throwable) {
            if ($this->symfonyStyle->isVerbose() || StaticPHPUnitEnvironment::isPHPUnitRun()) {
                throw $throwable;
            }
            $relativeFilePath = $this->filePathHelper->relativePath($file->getFilePath());
            if ($throwable instanceof ParserErrorsException) {
                $throwable = new ParserErrors($throwable);
            }
            return new SystemError($throwable->getMessage(), $relativeFilePath, $throwable->getLine());
        }
        return null;
    }
    private function printFile(File $file, Configuration $configuration, string $filePath): void
    {
        // only save to string first, no need to print to file when not needed
        $newFileContent = $this->betterStandardPrinter->printFormatPreserving($file->getNewStmts(), $file->getOldStmts(), $file->getOldTokens());
        // change file content early to make $file->hasChanged() based on new content
        $file->changeFileContent($newFileContent);
        if ($configuration->isDryRun()) {
            return;
        }
        if (!$file->hasChanged()) {
            return;
        }
        FileSystem::write($filePath, $newFileContent, null);
    }
    private function parseFileNodes(File $file, bool $forNewestSupportedVersion = \true): void
    {
        // store tokens by original file content, so we don't have to print them right now
        $stmtsAndTokens = $this->rectorParser->parseFileContentToStmtsAndTokens($file->getOriginalFileContent(), $forNewestSupportedVersion);
        $oldStmts = $stmtsAndTokens->getStmts();
        // resolve names up front, so used imports (incl. the class FQN) are resolvable at construction,
        // before scope decoration runs; only annotates namespacedName, does not replace name nodes
        $nameResolvingNodeTraverser = new NodeTraverser(new NameResolver(null, ['preserveOriginalNames' => \true, 'replaceNodes' => \false]));
        $nameResolvingNodeTraverser->traverse($oldStmts);
        // wrap in FileNode to allow file-level rules; seed used imports once, kept in sync incrementally
        $usedImports = $this->usedImportsResolver->resolveForStmts($oldStmts);
        $oldStmts = [new FileNode($oldStmts, $usedImports)];
        $oldTokens = $stmtsAndTokens->getTokens();
        $newStmts = $this->nodeScopeAndMetadataDecorator->decorateNodesFromFile($file->getFilePath(), $oldStmts);
        $file->hydrateStmtsAndTokens($newStmts, $oldStmts, $oldTokens);
    }
}
