<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Bootstrap;

use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\ArgvInput;
/**
 * A different extra autoload changes what PHPStan can resolve, so cached results
 * must not survive it. Registering the file as a parameter folds it into the
 * configuration hash, which invalidates the cache like any other config change.
 *
 * Called from bin/code-upgrade.php before the configuration hash is computed.
 *
 * @see \Flames\Code\Upgrade\Tests\Bootstrap\AutoloadFileParameterResolverTest
 */
final class AutoloadFileParameterResolver
{
    /**
     * @param array<int, mixed> $argv
     */
    public static function resolveFromArgv(array $argv): void
    {
        // handles "--autoload-file path", "--autoload-file=path" and "-a path";
        // parallel workers receive the space-separated form, so all spellings must
        // normalize to the same value or main process and workers would disagree
        $autoloadFile = new ArgvInput($argv)->getParameterOption(['--autoload-file', '-a'], null);
        if (!is_string($autoloadFile) || $autoloadFile === '') {
            return;
        }
        SimpleParameterProvider::setParameter(Option::AUTOLOAD_FILE, realpath($autoloadFile) ?: $autoloadFile);
    }
}
