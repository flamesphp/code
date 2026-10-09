<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\Enum;

final class MatchKind
{
    public const string NORMAL = 'normal';
    public const string ASSIGN = 'assign';
    public const string RETURN = 'return';
    public const string THROW = 'throw';
}
