<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use function array_filter;
use function array_values;
use function count;
#[AutowiredService]
final class UnaryOperatorTypeSpecifyingExtensionRegistry
{
    /**
     * @var ExtensionsCollection<UnaryOperatorTypeSpecifyingExtension>
     */
    private ExtensionsCollection $extensions;
    /**
     * @param ExtensionsCollection<UnaryOperatorTypeSpecifyingExtension> $extensions
     */
    public function __construct(
        #[AutowiredExtensions(\PHPStan\Type\UnaryOperatorTypeSpecifyingExtension::class)]
        ExtensionsCollection $extensions
    )
    {
        $this->extensions = $extensions;
    }
    /**
     * @return UnaryOperatorTypeSpecifyingExtension[]
     */
    private function getOperatorTypeSpecifyingExtensions(string $operator, \PHPStan\Type\Type $operandType): array
    {
        return array_values(array_filter($this->extensions->getAll(), static fn(\PHPStan\Type\UnaryOperatorTypeSpecifyingExtension $extension): bool => $extension->isOperatorSupported($operator, $operandType)));
    }
    public function callUnaryOperatorTypeSpecifyingExtensions(string $operatorSigil, \PHPStan\Type\Type $operandType): ?\PHPStan\Type\Type
    {
        $operatorTypeSpecifyingExtensions = $this->getOperatorTypeSpecifyingExtensions($operatorSigil, $operandType);
        /** @var list<Type> $extensionTypes */
        $extensionTypes = [];
        foreach ($operatorTypeSpecifyingExtensions as $extension) {
            $extensionTypes[] = $extension->specifyType($operatorSigil, $operandType);
        }
        if (count($extensionTypes) > 0) {
            return \PHPStan\Type\TypeCombinator::union(...$extensionTypes);
        }
        return null;
    }
}
