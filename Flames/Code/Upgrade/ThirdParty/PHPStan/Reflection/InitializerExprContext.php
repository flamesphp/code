<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PhpParser\Node\PropertyHook;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function array_slice;
use function count;
use function explode;
use function implode;
use function sprintf;
/**
 * @api
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/InitializerExprContext.cpp')]
final class InitializerExprContext implements \PHPStan\Reflection\NamespaceAnswerer
{
    private ?string $file;
    /**
     * @var non-empty-string|null
     */
    private ?string $namespace;
    private ?string $className;
    private ?string $traitName;
    private ?string $function;
    private ?string $method;
    private ?string $property;
    /**
     * @param non-empty-string|null $namespace
     */
    private function __construct(?string $file, ?string $namespace, ?string $className, ?string $traitName, ?string $function, ?string $method, ?string $property)
    {
        $this->file = $file;
        $this->namespace = $namespace;
        $this->className = $className;
        $this->traitName = $traitName;
        $this->function = $function;
        $this->method = $method;
        $this->property = $property;
    }
    public static function fromScope(Scope $scope): self
    {
        $function = $scope->getFunction();
        // __FILE__ and __DIR__ are resolved at compile time, so in a trait context they
        // point at the file the trait is declared in, while Scope::getFile() returns the
        // file of the class that uses the trait.
        $file = $scope->getFile();
        if ($scope->isInTrait()) {
            $traitFileName = $scope->getTraitReflection()->getFileName();
            if ($traitFileName !== null) {
                $file = $traitFileName;
            }
        }
        return new self($file, $scope->getNamespace(), $scope->isInClass() ? $scope->getClassReflection()->getName() : null, $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null, $scope->isInAnonymousFunction() ? '{closure}' : ($function !== null ? $function->getName() : null), $scope->isInAnonymousFunction() ? '{closure}' : ($function instanceof \PHPStan\Reflection\MethodReflection ? sprintf('%s::%s', $function->getDeclaringClass()->getName(), $function->getName()) : ($function instanceof \PHPStan\Reflection\FunctionReflection ? $function->getName() : null)), $function instanceof PhpMethodFromParserNodeReflection && $function->hookForProperty !== null ? $function->hookForProperty : null);
    }
    /**
     * @return non-empty-string|null
     */
    private static function parseNamespace(string $name): ?string
    {
        $parts = explode('\\', $name);
        if (count($parts) > 1) {
            $ns = implode('\\', array_slice($parts, 0, -1));
            if ($ns === '') {
                throw new ShouldNotHappenException('Namespace cannot be empty.');
            }
            return $ns;
        }
        return null;
    }
    public static function fromClassReflection(\PHPStan\Reflection\ClassReflection $classReflection): self
    {
        return self::fromClass($classReflection->getName(), $classReflection->getFileName());
    }
    public static function fromClass(string $className, ?string $fileName): self
    {
        return new self($fileName, self::parseNamespace($className), $className, null, null, null, null);
    }
    public static function fromFunction(string $functionName, ?string $fileName): self
    {
        return new self($fileName, self::parseNamespace($functionName), null, null, $functionName, $functionName, null);
    }
    public static function fromClassMethod(string $className, ?string $traitName, string $methodName, ?string $fileName): self
    {
        return new self($fileName, self::parseNamespace($className), $className, $traitName, $methodName, sprintf('%s::%s', $className, $methodName), null);
    }
    public static function fromReflectionParameter(ReflectionParameter $parameter): self
    {
        $declaringFunction = $parameter->getDeclaringFunction();
        if ($declaringFunction instanceof ReflectionFunction) {
            $file = $declaringFunction->getFileName();
            return new self($file === \false ? null : $file, self::parseNamespace($declaringFunction->betterReflectionFunction->getName()), null, null, $declaringFunction->betterReflectionFunction->getName(), $declaringFunction->betterReflectionFunction->getName(), null);
        }
        $file = $declaringFunction->getFileName();
        $betterReflection = $declaringFunction->betterReflectionMethod;
        return new self($file === \false ? null : $file, self::parseNamespace($betterReflection->getDeclaringClass()->getName()), $declaringFunction->getDeclaringClass()->betterReflectionClass->getName(), $betterReflection->getDeclaringClass()->isTrait() ? $betterReflection->getDeclaringClass()->getName() : null, $declaringFunction->betterReflectionMethod->getName(), sprintf('%s::%s', $declaringFunction->getDeclaringClass()->betterReflectionClass->getName(), $declaringFunction->betterReflectionMethod->getName()), null);
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\PropertyHook $function
     */
    public static function fromStubParameter(?string $className, string $stubFile, $function): self
    {
        $namespace = null;
        if ($className !== null) {
            $namespace = self::parseNamespace($className);
        } else if ($function instanceof Function_ && $function->namespacedName !== null) {
            $namespace = self::parseNamespace($function->namespacedName->name);
        }
        $functionName = null;
        $propertyName = null;
        if ($function instanceof Function_ && $function->namespacedName !== null) {
            $functionName = $function->namespacedName->name;
        } elseif ($function instanceof ClassMethod) {
            $functionName = $function->name->name;
        } elseif ($function instanceof PropertyHook) {
            $propertyName = $function->getAttribute('propertyName');
            $functionName = sprintf('$%s::%s', $propertyName, $function->name->name);
        }
        $methodName = null;
        if ($function instanceof ClassMethod && $className !== null) {
            $methodName = sprintf('%s::%s', $className, $function->name->name);
        } elseif ($function instanceof PropertyHook) {
            $propertyName = $function->getAttribute('propertyName');
            $methodName = sprintf('%s::$%s::%s', $className, $propertyName, $function->name->name);
        } elseif ($function instanceof Function_ && $function->namespacedName !== null) {
            $methodName = $function->namespacedName->name;
        }
        return new self($stubFile, $namespace, $className, null, $functionName, $methodName, $propertyName);
    }
    public static function fromGlobalConstant(ReflectionConstant $constant): self
    {
        return new self($constant->locatedSource->getFileName(), $constant->namespace, null, null, null, null, null);
    }
    public static function createEmpty(): self
    {
        return new self(null, null, null, null, null, null, null);
    }
    public function getFile(): ?string
    {
        return $this->file;
    }
    public function getClassName(): ?string
    {
        return $this->className;
    }
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }
    public function getTraitName(): ?string
    {
        return $this->traitName;
    }
    public function getFunction(): ?string
    {
        return $this->function;
    }
    public function getMethod(): ?string
    {
        return $this->method;
    }
    public function getProperty(): ?string
    {
        return $this->property;
    }
}
