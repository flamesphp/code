<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PhpParser\Node\Stmt\Const_;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_keys;
use function hash_file;
use function sprintf;
use function strtolower;
#[GenerateFactory(\PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory::class)]
final class OptimizedSingleFileSourceLocator implements SourceLocator
{
    private \PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher $fileNodesFetcher;
    private Cache $cache;
    private PhpVersion $phpVersion;
    private string $fileName;
    /** @var array{classes: array<string, true>, functions: array<string, true>, constants: array<string, true>}|null */
    private ?array $presentSymbols = null;
    public function __construct(\PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher $fileNodesFetcher, Cache $cache, PhpVersion $phpVersion, string $fileName)
    {
        $this->fileNodesFetcher = $fileNodesFetcher;
        $this->cache = $cache;
        $this->phpVersion = $phpVersion;
        $this->fileName = $fileName;
    }
    private function getVariableCacheKey(string $file): string
    {
        $fileHash = hash_file('sha256', $file);
        if ($fileHash === \false) {
            throw new CouldNotReadFileException($file);
        }
        return sprintf('v2-%s-%s', $fileHash, $this->phpVersion->getVersionString());
    }
    /**
     * All symbols this file declares, lowercased (constants case-normalized).
     *
     * Populated from the cache or by fetching the file's nodes; used by
     * OptimizedPsrAutoloaderLocator to index known locators by symbol instead
     * of sweeping them linearly on every lookup.
     *
     * @internal
     * @return array{classes: array<string, true>, functions: array<string, true>, constants: array<string, true>}
     */
    public function getPresentSymbols(): array
    {
        if ($this->presentSymbols !== null) {
            return $this->presentSymbols;
        }
        $variableCacheKey = $this->getVariableCacheKey($this->fileName);
        $presentSymbolsCacheKey = sprintf('osfsl-%s-presentSymbols', $this->fileName);
        $cached = $this->cache->load($presentSymbolsCacheKey, $variableCacheKey);
        if ($cached !== null) {
            return $this->presentSymbols = $cached;
        }
        $fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($this->fileName);
        $presentSymbols = ['classes' => [], 'functions' => [], 'constants' => []];
        foreach (array_keys($fetchedNodesResult->classNodes) as $className) {
            $presentSymbols['classes'][$className] = \true;
        }
        foreach (array_keys($fetchedNodesResult->functionNodes) as $functionName) {
            $presentSymbols['functions'][$functionName] = \true;
        }
        foreach (array_keys($fetchedNodesResult->constantNodes) as $constantName) {
            $presentSymbols['constants'][$constantName] = \true;
        }
        $this->presentSymbols = $presentSymbols;
        $this->cache->save($presentSymbolsCacheKey, $variableCacheKey, $presentSymbols);
        return $presentSymbols;
    }
    #[Override]
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        if ($this->presentSymbols === null) {
            $variableCacheKey = $this->getVariableCacheKey($this->fileName);
            $this->presentSymbols = $this->cache->load(sprintf('osfsl-%s-presentSymbols', $this->fileName), $variableCacheKey);
        }
        if ($this->presentSymbols !== null) {
            if ($identifier->type->isClass()) {
                $className = strtolower($identifier->name);
                if (!array_key_exists($className, $this->presentSymbols['classes'])) {
                    return null;
                }
            }
            if ($identifier->type->isFunction()) {
                $className = strtolower($identifier->name);
                if (!array_key_exists($className, $this->presentSymbols['functions'])) {
                    return null;
                }
            }
            if ($identifier->type->isConstant()) {
                $constantName = ConstantNameHelper::normalize($identifier->name);
                if (!array_key_exists($constantName, $this->presentSymbols['constants'])) {
                    return null;
                }
            }
        }
        $reflectionCacheKey = sprintf('osfsl-%s-%s-%s', $this->fileName, $identifier->getType()->name, $identifier->name);
        $variableCacheKey ??= $this->getVariableCacheKey($this->fileName);
        $reflectionVariableCacheKey = sprintf('%s-%s', $variableCacheKey, ComposerHelper::getBetterReflectionVersion());
        $cachedReflection = $this->cache->load($reflectionCacheKey, $reflectionVariableCacheKey);
        if ($cachedReflection !== null) {
            if ($identifier->type->isConstant()) {
                return ReflectionConstant::importFromCache($reflector, $cachedReflection);
            }
            if ($identifier->type->isFunction()) {
                return ReflectionFunction::importFromCache($reflector, $cachedReflection);
            }
            if ($identifier->type->isClass()) {
                if (array_key_exists('backingType', $cachedReflection)) {
                    return ReflectionEnum::importFromCache($reflector, $cachedReflection);
                }
                return ReflectionClass::importFromCache($reflector, $cachedReflection);
            }
        }
        $fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($this->fileName);
        if ($this->presentSymbols === null) {
            $presentSymbols = ['classes' => [], 'functions' => [], 'constants' => []];
            foreach (array_keys($fetchedNodesResult->classNodes) as $className) {
                $presentSymbols['classes'][$className] = \true;
            }
            foreach (array_keys($fetchedNodesResult->functionNodes) as $functionName) {
                $presentSymbols['functions'][$functionName] = \true;
            }
            foreach (array_keys($fetchedNodesResult->constantNodes) as $constantName) {
                $presentSymbols['constants'][$constantName] = \true;
            }
            $this->presentSymbols = $presentSymbols;
            $this->cache->save(sprintf('osfsl-%s-presentSymbols', $this->fileName), $variableCacheKey, $presentSymbols);
        }
        $nodeToReflection = new NodeToReflection();
        if ($identifier->type->isClass()) {
            $classNodes = $fetchedNodesResult->classNodes;
            $className = strtolower($identifier->name);
            if (!array_key_exists($className, $classNodes)) {
                return null;
            }
            foreach ($classNodes[$className] as $classNode) {
                $classReflection = $nodeToReflection->__invoke($reflector, $classNode->node, $classNode->locatedSource, $classNode->namespace);
                if (!$classReflection instanceof ReflectionClass) {
                    throw new ShouldNotHappenException();
                }
                $this->cache->save($reflectionCacheKey, $reflectionVariableCacheKey, $classReflection->exportToCache());
                return $classReflection;
            }
        }
        if ($identifier->type->isFunction()) {
            $functionNodes = $fetchedNodesResult->functionNodes;
            $functionName = strtolower($identifier->name);
            if (!array_key_exists($functionName, $functionNodes)) {
                return null;
            }
            foreach ($functionNodes[$functionName] as $functionNode) {
                $functionReflection = $nodeToReflection->__invoke($reflector, $functionNode->node, $functionNode->locatedSource, $functionNode->namespace);
                if (!$functionReflection instanceof ReflectionFunction) {
                    throw new ShouldNotHappenException();
                }
                $this->cache->save($reflectionCacheKey, $reflectionVariableCacheKey, $functionReflection->exportToCache());
                return $functionReflection;
            }
        }
        if ($identifier->type->isConstant()) {
            $constantNodes = $fetchedNodesResult->constantNodes;
            $constantName = ConstantNameHelper::normalize($identifier->name);
            if (!array_key_exists($constantName, $constantNodes)) {
                return null;
            }
            foreach ($constantNodes[$constantName] as $fetchedConstantNode) {
                $constantNode = $fetchedConstantNode->node;
                $positionInNode = null;
                if ($constantNode instanceof Const_) {
                    foreach ($constantNode->consts as $constPosition => $const) {
                        if ($const->namespacedName === null) {
                            throw new ShouldNotHappenException();
                        }
                        if (ConstantNameHelper::normalize($const->namespacedName->name) === $constantName) {
                            /** @var int $positionInNode */
                            $positionInNode = $constPosition;
                            break;
                        }
                    }
                    if ($positionInNode === null) {
                        throw new ShouldNotHappenException();
                    }
                }
                $constantReflection = $nodeToReflection->__invoke($reflector, $fetchedConstantNode->node, $fetchedConstantNode->locatedSource, $fetchedConstantNode->namespace, $positionInNode);
                if (!$constantReflection instanceof ReflectionConstant) {
                    throw new ShouldNotHappenException();
                }
                $this->cache->save($reflectionCacheKey, $reflectionVariableCacheKey, $constantReflection->exportToCache());
                return $constantReflection;
            }
            return null;
        }
        throw new ShouldNotHappenException();
    }
    #[Override]
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        $fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($this->fileName);
        $nodeToReflection = new NodeToReflection();
        $reflections = [];
        if ($identifierType->isClass()) {
            $classNodes = $fetchedNodesResult->classNodes;
            foreach ($classNodes as $classNodesArray) {
                foreach ($classNodesArray as $classNode) {
                    $classReflection = $nodeToReflection->__invoke($reflector, $classNode->node, $classNode->locatedSource, $classNode->namespace);
                    if (!$classReflection instanceof ReflectionClass) {
                        throw new ShouldNotHappenException();
                    }
                    $reflections[] = $classReflection;
                }
            }
        }
        if ($identifierType->isFunction()) {
            $functionNodes = $fetchedNodesResult->functionNodes;
            foreach ($functionNodes as $functionNodesArray) {
                foreach ($functionNodesArray as $functionNode) {
                    $functionReflection = $nodeToReflection->__invoke($reflector, $functionNode->node, $functionNode->locatedSource, $functionNode->namespace);
                    $reflections[] = $functionReflection;
                }
            }
        }
        if ($identifierType->isConstant()) {
            $constantNodes = $fetchedNodesResult->constantNodes;
            foreach ($constantNodes as $constantNodesArray) {
                foreach ($constantNodesArray as $fetchedConstantNode) {
                    $constantNode = $fetchedConstantNode->node;
                    if ($constantNode instanceof Const_) {
                        foreach ($constantNode->consts as $constPosition => $const) {
                            if ($const->namespacedName === null) {
                                throw new ShouldNotHappenException();
                            }
                            $constantReflection = $nodeToReflection->__invoke($reflector, $constantNode, $fetchedConstantNode->locatedSource, $fetchedConstantNode->namespace, $constPosition);
                            if (!$constantReflection instanceof ReflectionConstant) {
                                throw new ShouldNotHappenException();
                            }
                            $reflections[] = $constantReflection;
                        }
                        continue;
                    }
                    $constantReflection = $nodeToReflection->__invoke($reflector, $constantNode, $fetchedConstantNode->locatedSource, $fetchedConstantNode->namespace);
                    if (!$constantReflection instanceof ReflectionConstant) {
                        throw new ShouldNotHappenException();
                    }
                    $reflections[] = $constantReflection;
                }
            }
        }
        return $reflections;
    }
}
