<?php

declare (strict_types=1);
namespace PHPStan\Type\Constant;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @api
 */
#[ReferencedByTurboExtension('constantArrayTypeAndMethod')]
final class ConstantArrayTypeAndMethod
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $type;
    private ?string $method;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public TrinaryLogic $certainty;
    private function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $type,
        ?string $method,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        TrinaryLogic $certainty
    )
    {
        $this->type = $type;
        $this->method = $method;
        $this->certainty = $certainty;
    }
    public static function createConcrete(Type $type, string $method, TrinaryLogic $certainty): self
    {
        if ($certainty->no()) {
            throw new ShouldNotHappenException();
        }
        return new self($type, $method, $certainty);
    }
    public static function createUnknown(): self
    {
        return new self(null, null, TrinaryLogic::createMaybe());
    }
    public function isUnknown(): bool
    {
        return $this->type === null;
    }
    public function getType(): Type
    {
        if ($this->type === null) {
            throw new ShouldNotHappenException();
        }
        return $this->type;
    }
    public function getMethod(): string
    {
        if ($this->method === null) {
            throw new ShouldNotHappenException();
        }
        return $this->method;
    }
    public function getCertainty(): TrinaryLogic
    {
        return $this->certainty;
    }
}
