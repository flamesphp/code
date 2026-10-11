<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\Contract;

interface RenameAnnotationInterface
{
    public function getOldAnnotation(): string;
    public function getNewAnnotation(): string;
}
