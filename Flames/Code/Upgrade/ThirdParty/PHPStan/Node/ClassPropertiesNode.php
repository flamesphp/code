<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Method\MethodCall;
use PHPStan\Node\Property\PropertyAssign;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Node\Property\PropertyWrite;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeUtils;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function in_array;
use function strtolower;
/**
 * @api
 */
#[ReferencedByTurboExtension('classPropertiesNode')]
final class ClassPropertiesNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassLike $class;
    /**
     * @var ExtensionsCollection<ReadWritePropertiesExtension>
     */
    private ExtensionsCollection $readWritePropertiesExtensions;
    /**
     * @var ClassPropertyNode[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $properties;
    /**
     * @var array<int, PropertyRead|PropertyWrite>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $propertyUsages;
    /**
     * @var array<int, MethodCall>
     */
    private array $methodCalls;
    /**
     * @var array<string, MethodReturnStatementsNode>
     */
    private array $returnStatementNodes;
    /**
     * @var list<PropertyAssign>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $propertyAssigns;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $classReflection;
    /**
     * @param ExtensionsCollection<ReadWritePropertiesExtension> $readWritePropertiesExtensions
     * @param ClassPropertyNode[] $properties
     * @param array<int, PropertyRead|PropertyWrite> $propertyUsages
     * @param array<int, MethodCall> $methodCalls
     * @param array<string, MethodReturnStatementsNode> $returnStatementNodes
     * @param list<PropertyAssign> $propertyAssigns
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassLike $class,
        ExtensionsCollection $readWritePropertiesExtensions,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $properties,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $propertyUsages,
        array $methodCalls,
        array $returnStatementNodes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $propertyAssigns,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $classReflection
    )
    {
        $this->class = $class;
        $this->readWritePropertiesExtensions = $readWritePropertiesExtensions;
        $this->properties = $properties;
        $this->propertyUsages = $propertyUsages;
        $this->methodCalls = $methodCalls;
        $this->returnStatementNodes = $returnStatementNodes;
        $this->propertyAssigns = $propertyAssigns;
        $this->classReflection = $classReflection;
        parent::__construct($class->getAttributes());
    }
    public function getClass(): ClassLike
    {
        return $this->class;
    }
    /**
     * @return ClassPropertyNode[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
    /**
     * @return array<int, PropertyRead|PropertyWrite>
     */
    public function getPropertyUsages(): array
    {
        return $this->propertyUsages;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_ClassPropertiesNode';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    /**
     * @param string[] $constructors
     * @return array{array<string, ClassPropertyNode>, array<array{string, int, ClassPropertyNode, string, string}>, array<array{string, int, ClassPropertyNode}>}
     */
    public function getUninitializedProperties(Scope $scope, array $constructors): array
    {
        if (!$this->class instanceof Class_) {
            return [[], [], []];
        }
        $classReflection = $this->classReflection;
        $uninitializedProperties = [];
        $originalProperties = [];
        $initialInitializedProperties = [];
        $initializedProperties = [];
        $extensions = $this->readWritePropertiesExtensions->getAll();
        $initializedViaExtension = [];
        foreach ($this->properties as $property) {
            if ((bool) ($property->flags & \PhpParser\Modifiers::STATIC)) {
                continue;
            }
            if ((bool) ($property->flags & \PhpParser\Modifiers::ABSTRACT)) {
                continue;
            }
            if ($property->type === null) {
                continue;
            }
            if ($property->default !== null) {
                continue;
            }
            $originalProperties[$property->name] = $property;
            $is = TrinaryLogic::createFromBoolean($property->isPromoted && !$property->isPromotedFromTrait);
            if (!$is->yes() && $classReflection->hasNativeProperty($property->name)) {
                $propertyReflection = $classReflection->getNativeProperty($property->name);
                if ($propertyReflection->isVirtual()->yes()) {
                    continue;
                }
                if ($propertyReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
                    $is = TrinaryLogic::createYes();
                }
                foreach ($extensions as $extension) {
                    if (!$extension->isInitialized($propertyReflection, $property->name)) {
                        continue;
                    }
                    $is = TrinaryLogic::createYes();
                    $initializedViaExtension[$property->name] = \true;
                    break;
                }
            }
            $initialInitializedProperties[$property->name] = $is;
            foreach ($constructors as $constructor) {
                $initializedProperties[$constructor][$property->name] = $is;
            }
            if ($is->yes()) {
                continue;
            }
            $uninitializedProperties[$property->name] = $property;
        }
        if ($constructors === []) {
            return [$uninitializedProperties, [], []];
        }
        $initializedInConstructor = [];
        if ($classReflection->hasConstructor()) {
            $initializedInConstructor = array_diff_key($uninitializedProperties, $this->collectUninitializedProperties([$classReflection->getConstructor()->getName()], $uninitializedProperties));
        }
        $methodsCalledFromConstructor = $this->getMethodsCalledFromConstructor($classReflection, $initialInitializedProperties, $initializedProperties, $constructors, $initializedInConstructor, $constructors);
        $prematureAccess = [];
        $additionalAssigns = [];
        foreach ($this->propertyUsages as $usage) {
            $fetch = $usage->getFetch();
            if (!$fetch instanceof PropertyFetch) {
                continue;
            }
            $usageScope = $usage->getScope();
            if ($usageScope->getFunction() === null) {
                continue;
            }
            $function = $usageScope->getFunction();
            if (!$function instanceof MethodReflection) {
                continue;
            }
            if ($function->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }
            if (!array_key_exists($function->getName(), $methodsCalledFromConstructor)) {
                continue;
            }
            $initializedPropertiesMap = $methodsCalledFromConstructor[$function->getName()];
            if (!$fetch->name instanceof Identifier) {
                continue;
            }
            $propertyName = $fetch->name->name;
            $fetchedOnType = $usageScope->getType($fetch->var);
            if (TypeUtils::findThisType($fetchedOnType) === null) {
                continue;
            }
            $propertyReflection = $usageScope->getInstancePropertyReflection($fetchedOnType, $propertyName);
            if ($propertyReflection === null) {
                continue;
            }
            if ($propertyReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }
            if ($usage instanceof PropertyWrite) {
                if (array_key_exists($propertyName, $initializedPropertiesMap)) {
                    $hasInitialization = $initializedPropertiesMap[$propertyName];
                    if (in_array($function->getName(), $constructors, \true)) {
                        $hasInitialization = $hasInitialization->or($usageScope->hasExpressionType(new PropertyInitializationExpr($propertyName)));
                    }
                    if (!$hasInitialization->no() && !$usage->promotedPropertyWrite && !$usage->isViaOffsetAccess() && !array_key_exists($propertyName, $initializedViaExtension)) {
                        $additionalAssigns[] = [$propertyName, $fetch->getStartLine(), $originalProperties[$propertyName]];
                    }
                }
            } elseif (array_key_exists($propertyName, $initializedPropertiesMap)) {
                if (strtolower($function->getName()) !== '__construct' && array_key_exists($propertyName, $initializedInConstructor) && in_array($function->getName(), $constructors, \true)) {
                    continue;
                }
                $hasInitialization = $initializedPropertiesMap[$propertyName]->or($usageScope->hasExpressionType(new PropertyInitializationExpr($propertyName)));
                if (!$hasInitialization->yes() && $usageScope->isInAnonymousFunction() && $usageScope->getParentScope() !== null) {
                    $hasInitialization = $hasInitialization->or($usageScope->getParentScope()->hasExpressionType(new PropertyInitializationExpr($propertyName)));
                }
                if (!$hasInitialization->yes()) {
                    $prematureAccess[] = [$propertyName, $fetch->getStartLine(), $originalProperties[$propertyName], $usageScope->getFile(), $usageScope->getFileDescription()];
                }
            }
        }
        return [$this->collectUninitializedProperties(array_keys($methodsCalledFromConstructor), $uninitializedProperties), $prematureAccess, $additionalAssigns];
    }
    /**
     * @param list<string> $constructors
     * @param array<string, ClassPropertyNode> $uninitializedProperties
     * @return array<string, ClassPropertyNode>
     */
    private function collectUninitializedProperties(array $constructors, array $uninitializedProperties): array
    {
        foreach ($constructors as $constructor) {
            $lowerConstructorName = strtolower($constructor);
            if (!array_key_exists($lowerConstructorName, $this->returnStatementNodes)) {
                continue;
            }
            $returnStatementsNode = $this->returnStatementNodes[$lowerConstructorName];
            $methodScope = null;
            foreach ($returnStatementsNode->executionEnds as $executionEnd) {
                $statementResult = $executionEnd->statementResult;
                $endNode = $executionEnd->node;
                if ($statementResult->isAlwaysTerminating()) {
                    if ($endNode instanceof Node\Stmt\Expression) {
                        $exprType = $statementResult->getScope()->getType($endNode->expr);
                        if ($exprType instanceof NeverType && $exprType->isExplicit()) {
                            continue;
                        }
                    }
                }
                if ($methodScope === null) {
                    $methodScope = $statementResult->getScope();
                    continue;
                }
                $methodScope = $methodScope->mergeWith($statementResult->getScope());
            }
            foreach ($returnStatementsNode->returnStatements as $returnStatement) {
                if ($methodScope === null) {
                    $methodScope = $returnStatement->scope;
                    continue;
                }
                $methodScope = $methodScope->mergeWith($returnStatement->scope);
            }
            if ($methodScope === null) {
                continue;
            }
            foreach (array_keys($uninitializedProperties) as $propertyName) {
                if (!$methodScope->hasExpressionType(new PropertyInitializationExpr($propertyName))->yes()) {
                    continue;
                }
                unset($uninitializedProperties[$propertyName]);
            }
        }
        return $uninitializedProperties;
    }
    /**
     * @param string[] $methods
     * @param array<string, TrinaryLogic> $initialInitializedProperties
     * @param array<string, array<string, TrinaryLogic>> $initializedProperties
     * @param array<string, ClassPropertyNode> $initializedInConstructorProperties
     * @param string[] $originalConstructors
     *
     * @return array<string, array<string, TrinaryLogic>>
     */
    private function getMethodsCalledFromConstructor(ClassReflection $classReflection, array $initialInitializedProperties, array $initializedProperties, array $methods, array $initializedInConstructorProperties, array $originalConstructors): array
    {
        $originalMap = $initializedProperties;
        $originalMethods = $methods;
        foreach ($this->methodCalls as $methodCall) {
            $methodCallNode = $methodCall->node;
            if ($methodCallNode instanceof Array_) {
                continue;
            }
            if (!$methodCallNode->name instanceof Identifier) {
                continue;
            }
            $callScope = $methodCall->scope;
            if ($methodCallNode instanceof Node\Expr\MethodCall) {
                $calledOnType = $callScope->getType($methodCallNode->var);
            } else {
                if (!$methodCallNode->class instanceof Name) {
                    continue;
                }
                $calledOnType = $callScope->resolveTypeByName($methodCallNode->class);
            }
            if (TypeUtils::findThisType($calledOnType) === null) {
                continue;
            }
            $inMethod = $callScope->getFunction();
            if (!$inMethod instanceof MethodReflection) {
                continue;
            }
            if (!in_array($inMethod->getName(), $methods, \true)) {
                continue;
            }
            if ($inMethod->getName() !== '__construct' && in_array($inMethod->getName(), $originalConstructors, \true)) {
                foreach (array_keys($initializedInConstructorProperties) as $propertyName) {
                    $initializedProperties[$inMethod->getName()][$propertyName] = TrinaryLogic::createYes();
                }
            }
            $methodName = $methodCallNode->name->name;
            if (array_key_exists($methodName, $initializedProperties)) {
                foreach ($this->getInitializedProperties($callScope, $initializedProperties[$inMethod->getName()] ?? $initialInitializedProperties) as $propertyName => $isInitialized) {
                    $initializedProperties[$methodName][$propertyName] = $initializedProperties[$methodName][$propertyName]->and($isInitialized);
                }
                continue;
            }
            $methodReflection = $callScope->getMethodReflection($calledOnType, $methodName);
            if ($methodReflection === null) {
                continue;
            }
            if ($methodReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }
            $initializedProperties[$methodName] = $this->getInitializedProperties($callScope, $initializedProperties[$inMethod->getName()] ?? $initialInitializedProperties);
            $methods[] = $methodName;
        }
        if ($originalMap === $initializedProperties && $originalMethods === $methods) {
            return $initializedProperties;
        }
        return $this->getMethodsCalledFromConstructor($classReflection, $initialInitializedProperties, $initializedProperties, $methods, $initializedInConstructorProperties, $originalConstructors);
    }
    /**
     * @param array<string, TrinaryLogic> $initialInitializedProperties
     * @return array<string, TrinaryLogic>
     */
    private function getInitializedProperties(Scope $scope, array $initialInitializedProperties): array
    {
        foreach ($initialInitializedProperties as $propertyName => $isInitialized) {
            $initialInitializedProperties[$propertyName] = $isInitialized->or($scope->hasExpressionType(new PropertyInitializationExpr($propertyName)));
        }
        return $initialInitializedProperties;
    }
    /**
     * @return list<PropertyAssign>
     */
    public function getPropertyAssigns(): array
    {
        return $this->propertyAssigns;
    }
}
