<?php

declare (strict_types=1);
namespace PHPStan\Php;

use IteratorAggregate;
use Override;
use PHPStan\ShouldNotHappenException;
use Traversable;
use function floor;
/**
 * @api
 *
 * @implements IteratorAggregate<PhpVersion>
 */
final class PhpMinorVersionIterator implements IteratorAggregate
{
    private \PHPStan\Php\PhpVersion $endVersion;
    private \PHPStan\Php\PhpVersion $currentVersion;
    /** @api */
    public function __construct(\PHPStan\Php\PhpVersion $startVersion, \PHPStan\Php\PhpVersion $endVersion)
    {
        $this->endVersion = $endVersion;
        if ((int) \floor($startVersion->versionId / 10000) < 5 || (int) \floor($startVersion->versionId / 10000) > 8) {
            throw new ShouldNotHappenException();
        }
        if ((int) \floor($endVersion->versionId / 10000) < 5 || (int) \floor($endVersion->versionId / 10000) > 8) {
            throw new ShouldNotHappenException();
        }
        if ($startVersion->versionId > $this->endVersion->versionId) {
            throw new ShouldNotHappenException();
        }
        $this->currentVersion = $startVersion;
    }
    #[Override]
    public function getIterator(): Traversable
    {
        yield $this->currentVersion;
        while (\true) {
            if ((int) \floor($this->currentVersion->versionId / 10000) === 5 && (int) \floor($this->currentVersion->versionId % 10000 / 100) === 6) {
                $next = new \PHPStan\Php\PhpVersion(70000);
            } elseif ((int) \floor($this->currentVersion->versionId / 10000) === 7 && (int) \floor($this->currentVersion->versionId % 10000 / 100) === 4) {
                $next = new \PHPStan\Php\PhpVersion(80000);
            } else {
                $nextMinorVersionId = $this->currentVersion->versionId + 100;
                $nextWithZeroPatch = (int) floor($nextMinorVersionId / 100) * 100;
                $next = new \PHPStan\Php\PhpVersion($nextWithZeroPatch);
            }
            if ($next->versionId > $this->endVersion->versionId) {
                break;
            }
            $this->currentVersion = $next;
            yield $this->currentVersion;
        }
        if ($this->currentVersion->versionId === $this->endVersion->versionId) {
            return;
        }
        yield $this->endVersion;
    }
}
