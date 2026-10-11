<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('noopNodeCallback')]
final class NoopNodeCallback
{
    public function __invoke(Node $node, \PHPStan\Analyser\Scope $scope): void
    {
        // noop
    }
}
