<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
/**
 * @api
 */
#[ReferencedByTurboExtension('templateTag')]
final class TemplateTag
{
    /**
     * @var non-empty-string
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $name;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $bound;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $default;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public TemplateTypeVariance $variance;
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $name,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $bound,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $default,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        TemplateTypeVariance $variance
    )
    {
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
        $this->variance = $variance;
    }
    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }
    public function getBound(): Type
    {
        return $this->bound;
    }
    public function getDefault(): ?Type
    {
        return $this->default;
    }
    public function getVariance(): TemplateTypeVariance
    {
        return $this->variance;
    }
}
