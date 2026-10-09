<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\Contract;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
interface ConverterAttributeDecoratorInterface
{
    public function getAttributeName(): string;
    public function decorate(Attribute $attribute): void;
}
