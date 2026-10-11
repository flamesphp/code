<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/TemplateNullType.cpp')]
final class TemplateNullType extends NullType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<NullType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    /**
     * @param non-empty-string $name
     */
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, NullType $bound, ?Type $default)
    {
        parent::__construct();
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
    }
}
