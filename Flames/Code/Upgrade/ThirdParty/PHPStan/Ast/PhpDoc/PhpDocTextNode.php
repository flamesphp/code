<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
class PhpDocTextNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocChildNode
{
    use NodeAttributes;
    public function __construct(public string $text)
    {
    }
    public function __toString(): string
    {
        return $this->text;
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['text']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
