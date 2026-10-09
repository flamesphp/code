<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\Enum;

final class NonAssertNonStaticMethods
{
    /**
     * @var string[]
     */
    public const array ALL = ['createMock', 'atLeast', 'atLeastOnce', 'once', 'never', 'any', 'exactly', 'atMost', 'throwException', 'expectException', 'expectExceptionMessage', 'expectExceptionCode', 'expectExceptionMessageMatches'];
}
