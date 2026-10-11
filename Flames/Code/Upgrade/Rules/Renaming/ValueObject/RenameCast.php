<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
use Flames\Code\Upgrade\Validation\RectorAssert;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class RenameCast
{
    /**
     * @param class-string<Cast> $fromCastExprClass
     */
    public function __construct(private string $fromCastExprClass, private int $fromCastKind, private int $toCastKind)
    {
        RectorAssert::className($this->fromCastExprClass);
        Assert::subclassOf($this->fromCastExprClass, Cast::class);
    }
    /**
     * @return class-string<Cast>
     */
    public function getFromCastExprClass(): string
    {
        return $this->fromCastExprClass;
    }
    public function getFromCastKind(): int
    {
        return $this->fromCastKind;
    }
    public function getToCastKind(): int
    {
        return $this->toCastKind;
    }
}
