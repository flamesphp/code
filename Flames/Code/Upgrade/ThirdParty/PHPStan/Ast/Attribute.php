<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast;

final class Attribute
{
    public const string START_LINE = 'startLine';
    public const string END_LINE = 'endLine';
    public const string START_INDEX = 'startIndex';
    public const string END_INDEX = 'endIndex';
    public const string ORIGINAL_NODE = 'originalNode';
    public const string COMMENTS = 'comments';
}
