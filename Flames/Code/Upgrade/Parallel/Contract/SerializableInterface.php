<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Parallel\Contract;

use JsonSerializable;
interface SerializableInterface extends JsonSerializable
{
    /**
     * @param array<string, mixed> $json
     */
    public static function decode(array $json): self;
}
