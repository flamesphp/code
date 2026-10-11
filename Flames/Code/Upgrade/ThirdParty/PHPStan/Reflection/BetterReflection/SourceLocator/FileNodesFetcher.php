<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\NodeTraverser;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\DeduplicatingFileReader;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
#[AutowiredService]
final class FileNodesFetcher
{
    private \PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor $cachingVisitor;
    private Parser $parser;
    private DeduplicatingFileReader $fileReader;
    public function __construct(
        \PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor $cachingVisitor,
        #[AutowiredParameter('@defaultAnalysisParser')]
        Parser $parser,
        DeduplicatingFileReader $fileReader
    )
    {
        $this->cachingVisitor = $cachingVisitor;
        $this->parser = $parser;
        $this->fileReader = $fileReader;
    }
    public function fetchNodes(string $fileName): \PHPStan\Reflection\BetterReflection\SourceLocator\FetchedNodesResult
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($this->cachingVisitor);
        $contents = $this->fileReader->read($fileName);
        try {
            $ast = $this->parser->parseFile($fileName);
        } catch (ParserErrorsException $e) {
            return new \PHPStan\Reflection\BetterReflection\SourceLocator\FetchedNodesResult([], [], []);
        }
        $this->cachingVisitor->reset($fileName, $contents);
        $nodeTraverser->traverse($ast);
        $result = new \PHPStan\Reflection\BetterReflection\SourceLocator\FetchedNodesResult($this->cachingVisitor->classNodes, $this->cachingVisitor->functionNodes, $this->cachingVisitor->constantNodes);
        $this->cachingVisitor->reset($fileName, $contents);
        return $result;
    }
}
