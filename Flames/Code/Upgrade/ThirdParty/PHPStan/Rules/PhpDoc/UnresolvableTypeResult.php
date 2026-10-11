<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('unresolvableTypeResult')]
final class UnresolvableTypeResult
{
    /**
     * @readonly
     * @var list<string>
     */
    public array $reasons;
    /** @param list<string> $reasons */
    public function __construct(array $reasons)
    {
        $this->reasons = $reasons;
    }
}
