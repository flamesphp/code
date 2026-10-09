<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObject;

final class AttributeValueAndDocComment
{
    public function __construct(
        /**
         * @readonly
         */
        public string $attributeValue,
        /**
         * @readonly
         */
        public string $docComment
    )
    {
    }
}
