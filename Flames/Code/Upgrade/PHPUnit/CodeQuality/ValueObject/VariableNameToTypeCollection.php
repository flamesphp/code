<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject;

final class VariableNameToTypeCollection
{
    /**
     * @param VariableNameToType[] $variableNameToType
     */
    public function __construct(private array $variableNameToType)
    {
    }
    public function matchByVariableName(string $variableName): ?\Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\VariableNameToType
    {
        foreach ($this->variableNameToType as $variableNameToType) {
            if ($variableNameToType->getVariableName() !== $variableName) {
                continue;
            }
            return $variableNameToType;
        }
        return null;
    }
    public function remove(\Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\VariableNameToType $matchedNullableVariableNameToType): void
    {
        foreach ($this->variableNameToType as $key => $variableNamesToType) {
            if ($matchedNullableVariableNameToType !== $variableNamesToType) {
                continue;
            }
            unset($this->variableNameToType[$key]);
            break;
        }
    }
}
