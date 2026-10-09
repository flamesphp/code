<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Entropy\Attribute;

use Attribute;
use PHPUnit\Framework\TestCase;
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class RelatedTest
{
    /**
     * @param class-string<TestCase> $testClass
     */
    public function __construct(string $testClass)
    {
    }
}
