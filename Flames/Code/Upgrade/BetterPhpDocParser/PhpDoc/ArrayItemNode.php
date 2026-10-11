<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode;
use Stringable;
final class ArrayItemNode implements PhpDocTagValueNode
{
    use NodeAttributes;
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function __construct(public $value, public $key = null)
    {
    }
    public function __toString(): string
    {
        $value = '';
        if ($this->key !== null && !is_int($this->key)) {
            $value .= $this->key . '=';
        }
        if (is_array($this->value)) {
            foreach ($this->value as $singleValue) {
                $value .= $singleValue;
            }
        } elseif ($this->value instanceof \Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode) {
            $value .= '@' . ltrim((string) $this->value->identifierTypeNode, '@') . $this->value;
        } else {
            $value .= $this->value;
        }
        return $value;
    }
}
