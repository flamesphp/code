<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PHPStan\Type\Type;
/**
 * @api
 */
final class FoundTypeResult
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $type;
    /**
     * @var string[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $referencedClasses;
    /**
     * @var list<IdentifierRuleError>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $unknownClassErrors;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $tip;
    /**
     * @param string[] $referencedClasses
     * @param list<IdentifierRuleError> $unknownClassErrors
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $type,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $referencedClasses,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $unknownClassErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $tip
    )
    {
        $this->type = $type;
        $this->referencedClasses = $referencedClasses;
        $this->unknownClassErrors = $unknownClassErrors;
        $this->tip = $tip;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    /**
     * @return string[]
     */
    public function getReferencedClasses(): array
    {
        return $this->referencedClasses;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    public function getUnknownClassErrors(): array
    {
        return $this->unknownClassErrors;
    }
    public function getTip(): ?string
    {
        return $this->tip;
    }
}
