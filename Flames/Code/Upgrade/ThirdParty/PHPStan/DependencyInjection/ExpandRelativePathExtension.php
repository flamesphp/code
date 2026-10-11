<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Flames\Code\Upgrade\ThirdParty\Nette\DI\CompilerExtension;
use Flames\Code\Upgrade\ThirdParty\Nette\Schema\Expect;
use Flames\Code\Upgrade\ThirdParty\Nette\Schema\Schema;
use Override;
#[\PHPStan\DependencyInjection\ContainerExtension('expandRelativePaths')]
final class ExpandRelativePathExtension extends CompilerExtension
{
    #[Override]
    public function getConfigSchema(): Schema
    {
        return Expect::listOf('string');
    }
}
