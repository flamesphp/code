<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Flames\Code\Upgrade\ThirdParty\Nette\DI\Config\Adapter;
use Flames\Code\Upgrade\ThirdParty\Nette\Neon\Exception;
use Flames\Code\Upgrade\ThirdParty\Nette\Neon\Neon;
use Override;
use PHPStan\File\FileReader;
use function sprintf;
final class NeonCachedFileReader implements Adapter
{
    /**
     * @var list<string>
     */
    private array $expandRelativePaths;
    private \PHPStan\DependencyInjection\NeonAdapter $adapter;
    /** @var array<string, mixed[]> */
    private static array $decodedCache = [];
    /**
     * @param list<string> $expandRelativePaths
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $expandRelativePaths, array $parameters = [])
    {
        $this->expandRelativePaths = $expandRelativePaths;
        $this->adapter = new \PHPStan\DependencyInjection\NeonAdapter($this->expandRelativePaths, $parameters);
    }
    /**
     * @return mixed[]
     */
    #[Override]
    public function load(string $file): array
    {
        try {
            if (isset(self::$decodedCache[$file])) {
                $neon = self::$decodedCache[$file];
            } else {
                $contents = FileReader::read($file);
                $neon = (array) Neon::decode($contents);
                self::$decodedCache[$file] = $neon;
            }
            return $this->adapter->process($neon, '', $file);
        } catch (Exception $e) {
            throw new Exception(sprintf('Error while loading %s: %s', $file, $e->getMessage()));
        }
    }
}
