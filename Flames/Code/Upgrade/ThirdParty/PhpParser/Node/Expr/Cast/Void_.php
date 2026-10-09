<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
class Void_ extends Cast
{
    public function getType(): string
    {
        return 'Expr_Cast_Void';
    }
}
