<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\ArrayItem;
use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('literalArrayItem')]
final class LiteralArrayItem
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $scope;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?ArrayItem $arrayItem;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $scope,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?ArrayItem $arrayItem
    )
    {
        $this->scope = $scope;
        $this->arrayItem = $arrayItem;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
    public function getArrayItem(): ?ArrayItem
    {
        return $this->arrayItem;
    }
}
