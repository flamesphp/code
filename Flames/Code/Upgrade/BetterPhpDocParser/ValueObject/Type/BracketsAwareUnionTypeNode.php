<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\UnionTypeNode;
use Stringable;
final class BracketsAwareUnionTypeNode extends UnionTypeNode
{
    /**
     * @param TypeNode[] $types
     */
    public function __construct(array $types, private readonly bool $isWrappedInBrackets = \false)
    {
        parent::__construct($types);
    }
    /**
     * Preserve common format
     */
    public function __toString(): string
    {
        $types = [];
        // get the actual strings first before array_unique
        // to avoid similar object but different printing to be treated as unique
        foreach ($this->types as $type) {
            $types[] = (string) $type;
        }
        $types = array_unique($types);
        if (!$this->isWrappedInBrackets) {
            return implode('|', $types);
        }
        return '(' . implode('|', $types) . ')';
    }
    public function isWrappedInBrackets(): bool
    {
        return $this->isWrappedInBrackets;
    }
}
