<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Enum;

final class MagicPropertyHandler
{
    public const string GET = 'get';
    public const string SET = 'set';
    public const string ISSET_ = 'exists';
    public const string UNSET = 'unset';
}
