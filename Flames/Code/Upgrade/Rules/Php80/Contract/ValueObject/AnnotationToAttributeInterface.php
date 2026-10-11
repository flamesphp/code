<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\Contract\ValueObject;

interface AnnotationToAttributeInterface
{
    public function getTag(): string;
    public function getAttributeClass(): string;
}
