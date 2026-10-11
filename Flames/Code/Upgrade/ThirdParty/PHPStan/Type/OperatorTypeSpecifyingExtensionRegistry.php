<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Expr;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use function array_filter;
use function array_values;
use function count;
#[AutowiredService]
final class OperatorTypeSpecifyingExtensionRegistry
{
    /**
     * @var ExtensionsCollection<OperatorTypeSpecifyingExtension>
     */
    private ExtensionsCollection $extensions;
    /**
     * @param ExtensionsCollection<OperatorTypeSpecifyingExtension> $extensions
     */
    public function __construct(
        #[AutowiredExtensions(\PHPStan\Type\OperatorTypeSpecifyingExtension::class)]
        ExtensionsCollection $extensions
    )
    {
        $this->extensions = $extensions;
    }
    /**
     * @return OperatorTypeSpecifyingExtension[]
     */
    private function getOperatorTypeSpecifyingExtensions(string $operator, \PHPStan\Type\Type $leftType, \PHPStan\Type\Type $rightType): array
    {
        return array_values(array_filter($this->extensions->getAll(), static fn(\PHPStan\Type\OperatorTypeSpecifyingExtension $extension): bool => $extension->isOperatorSupported($operator, $leftType, $rightType)));
    }
    public function callOperatorTypeSpecifyingExtensions(Expr\BinaryOp $expr, \PHPStan\Type\Type $leftType, \PHPStan\Type\Type $rightType): ?\PHPStan\Type\Type
    {
        $operatorSigil = $expr->getOperatorSigil();
        $operatorTypeSpecifyingExtensions = $this->getOperatorTypeSpecifyingExtensions($operatorSigil, $leftType, $rightType);
        /** @var list<Type> $extensionTypes */
        $extensionTypes = [];
        foreach ($operatorTypeSpecifyingExtensions as $extension) {
            $extensionTypes[] = $extension->specifyType($operatorSigil, $leftType, $rightType);
        }
        if (count($extensionTypes) > 0) {
            return \PHPStan\Type\TypeCombinator::union(...$extensionTypes);
        }
        return null;
    }
}
