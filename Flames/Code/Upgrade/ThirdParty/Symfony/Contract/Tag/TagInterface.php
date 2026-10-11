<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Symfony\Contract\Tag;

interface TagInterface
{
    public function getName(): string;
    /**
     * @return array<string, mixed>
     */
    public function getData(): array;
}
