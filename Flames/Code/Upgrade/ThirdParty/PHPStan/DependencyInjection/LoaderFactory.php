<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Flames\Code\Upgrade\ThirdParty\Nette\DI\Config\Loader;
use PHPStan\Command\Environment;
use PHPStan\File\FileHelper;
final class LoaderFactory
{
    private FileHelper $fileHelper;
    private string $rootDir;
    private string $currentWorkingDirectory;
    private ?string $generateBaselineFile;
    /**
     * @var list<string>
     */
    private array $expandRelativePaths;
    /**
     * @param list<string> $expandRelativePaths
     */
    public function __construct(FileHelper $fileHelper, string $rootDir, string $currentWorkingDirectory, ?string $generateBaselineFile, array $expandRelativePaths)
    {
        $this->fileHelper = $fileHelper;
        $this->rootDir = $rootDir;
        $this->currentWorkingDirectory = $currentWorkingDirectory;
        $this->generateBaselineFile = $generateBaselineFile;
        $this->expandRelativePaths = $expandRelativePaths;
    }
    public function createLoader(): Loader
    {
        // known before the container is compiled, so the adapter can expand them in path entries
        // and the loader in included file names
        $parameters = ['rootDir' => $this->rootDir, 'currentWorkingDirectory' => $this->currentWorkingDirectory, 'env' => Environment::getCleanedArray()];
        $neonAdapter = new \PHPStan\DependencyInjection\NeonCachedFileReader($this->expandRelativePaths, $parameters);
        $loader = new \PHPStan\DependencyInjection\NeonLoader($this->fileHelper, $this->generateBaselineFile);
        $loader->addAdapter('dist', $neonAdapter);
        $loader->addAdapter('neon', $neonAdapter);
        $loader->setParameters($parameters);
        return $loader;
    }
}
