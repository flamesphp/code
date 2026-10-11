<?php

declare (strict_types=1);
namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[AutowiredService]
final class RestrictedFunctionUsageRule implements Rule
{
    /**
     * @var ExtensionsCollection<RestrictedFunctionUsageExtension>
     */
    private ExtensionsCollection $extensions;
    private ReflectionProvider $reflectionProvider;
    /**
     * @param ExtensionsCollection<RestrictedFunctionUsageExtension> $extensions
     */
    public function __construct(
        #[AutowiredExtensions(\PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension::class)]
        ExtensionsCollection $extensions,
        ReflectionProvider $reflectionProvider
    )
    {
        $this->extensions = $extensions;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }
    /**
     * @api
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }
        if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
            return [];
        }
        $functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
        $extensions = $this->extensions->getAll();
        $errors = [];
        foreach ($extensions as $extension) {
            $restrictedUsage = $extension->isRestrictedFunctionUsage($functionReflection, $scope);
            if ($restrictedUsage === null) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message($restrictedUsage->errorMessage)->identifier($restrictedUsage->identifier)->build();
        }
        return $errors;
    }
}
