<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;
#[AutowiredService]
final class PhpStormStubsSourceStubberFactory
{
    private Parser $phpParser;
    private Printer $printer;
    private PhpVersion $phpVersion;
    private int $phpStormStubsNodesCountMax;
    private \PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider $extensionVersionProvider;
    public function __construct(
        #[AutowiredParameter('@php8PhpParser')]
        Parser $phpParser,
        Printer $printer,
        PhpVersion $phpVersion,
        #[AutowiredParameter('%cache.phpStormStubsNodesCountMax%')]
        int $phpStormStubsNodesCountMax,
        \PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider $extensionVersionProvider
    )
    {
        $this->phpParser = $phpParser;
        $this->printer = $printer;
        $this->phpVersion = $phpVersion;
        $this->phpStormStubsNodesCountMax = $phpStormStubsNodesCountMax;
        $this->extensionVersionProvider = $extensionVersionProvider;
    }
    public function create(): PhpStormStubsSourceStubber
    {
        return new PhpStormStubsSourceStubber($this->phpParser, $this->printer, $this->phpVersion->versionId, $this->phpStormStubsNodesCountMax === 0 ? null : $this->phpStormStubsNodesCountMax, $this->extensionVersionProvider->getExtensionVersions());
    }
}
