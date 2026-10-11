<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
final readonly class SprintfStringAndArgs
{
    /**
     * @param Expr[] $arrayItems
     */
    public function __construct(private String_ $string, private array $arrayItems)
    {
    }
    /**
     * @return Expr[]
     */
    public function getArrayItems(): array
    {
        return $this->arrayItems;
    }
    public function getStringValue(): string
    {
        return $this->string->value;
    }
}
