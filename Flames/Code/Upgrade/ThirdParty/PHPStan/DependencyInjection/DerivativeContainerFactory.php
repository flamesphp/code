<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use function array_merge;
#[\PHPStan\DependencyInjection\AutowiredService]
final class DerivativeContainerFactory
{
    private string $currentWorkingDirectory;
    private string $tempDirectory;
    /**
     * @var string[]
     */
    private array $additionalConfigFiles;
    /**
     * @var string[]
     */
    private array $analysedPaths;
    /**
     * @var string[]
     */
    private array $composerAutoloaderProjectPaths;
    /**
     * @var string[]
     */
    private array $analysedPathsFromConfig;
    private string $usedLevel;
    private ?string $generateBaselineFile;
    private ?string $cliAutoloadFile;
    private ?string $singleReflectionFile;
    private ?string $singleReflectionInsteadOfFile;
    /**
     * @param string[] $additionalConfigFiles
     * @param string[] $analysedPaths
     * @param string[] $composerAutoloaderProjectPaths
     * @param string[] $analysedPathsFromConfig
     */
    public function __construct(
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        string $currentWorkingDirectory,
        #[\PHPStan\DependencyInjection\AutowiredParameter('%tempDir%')]
        string $tempDirectory,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        array $additionalConfigFiles,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        array $analysedPaths,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        array $composerAutoloaderProjectPaths,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        array $analysedPathsFromConfig,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        string $usedLevel,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        ?string $generateBaselineFile,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        ?string $cliAutoloadFile,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        ?string $singleReflectionFile,
        #[\PHPStan\DependencyInjection\AutowiredParameter]
        ?string $singleReflectionInsteadOfFile
    )
    {
        $this->currentWorkingDirectory = $currentWorkingDirectory;
        $this->tempDirectory = $tempDirectory;
        $this->additionalConfigFiles = $additionalConfigFiles;
        $this->analysedPaths = $analysedPaths;
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
        $this->analysedPathsFromConfig = $analysedPathsFromConfig;
        $this->usedLevel = $usedLevel;
        $this->generateBaselineFile = $generateBaselineFile;
        $this->cliAutoloadFile = $cliAutoloadFile;
        $this->singleReflectionFile = $singleReflectionFile;
        $this->singleReflectionInsteadOfFile = $singleReflectionInsteadOfFile;
    }
    /**
     * @param string[] $additionalConfigFiles
     * @param array<mixed> $additionalParameters
     */
    public function create(array $additionalConfigFiles, array $additionalParameters = []): \PHPStan\DependencyInjection\Container
    {
        $containerFactory = new \PHPStan\DependencyInjection\ContainerFactory($this->currentWorkingDirectory);
        $containerFactory->setJournalContainer();
        return $containerFactory->create($this->tempDirectory, array_merge($this->additionalConfigFiles, $additionalConfigFiles), $this->analysedPaths, $this->composerAutoloaderProjectPaths, $this->analysedPathsFromConfig, $this->usedLevel, $this->generateBaselineFile, $this->cliAutoloadFile, $this->singleReflectionFile, $this->singleReflectionInsteadOfFile, $additionalParameters);
    }
}
