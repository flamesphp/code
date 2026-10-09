<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Contract\DependencyInjection;

interface ResettableInterface
{
    public function reset(): void;
}
