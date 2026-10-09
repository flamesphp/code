<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\NodeAnalyzer;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\Naming\Naming\PropertyNaming;
use Flames\Code\Upgrade\NodeManipulator\ClassDependencyManipulator;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
use Flames\Code\Upgrade\PostRector\ValueObject\PropertyMetadata;
use Flames\Code\Upgrade\Rules\Transform\NodeFactory\PropertyFetchFactory;
use Flames\Code\Upgrade\Rules\Transform\NodeTypeAnalyzer\TypeProvidingExprFromClassResolver;
final readonly class FuncCallStaticCallToMethodCallAnalyzer
{
    public function __construct(private TypeProvidingExprFromClassResolver $typeProvidingExprFromClassResolver, private PropertyNaming $propertyNaming, private NodeNameResolver $nodeNameResolver, private NodeFactory $nodeFactory, private PropertyFetchFactory $propertyFetchFactory, private ClassDependencyManipulator $classDependencyManipulator)
    {
    }
    /**
     * @return \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\Variable|null
     */
    public function matchTypeProvidingExpr(Class_ $class, ClassMethod $classMethod, ObjectType $objectType)
    {
        $expr = $this->typeProvidingExprFromClassResolver->resolveTypeProvidingExprFromClass($class, $classMethod, $objectType);
        if ($expr instanceof Expr) {
            if ($expr instanceof Variable) {
                $this->addClassMethodParamForVariable($expr, $objectType, $classMethod);
            }
            return $expr;
        }
        // Cannot add constructor dependency when nearest parent constructor is final
        if ($this->classDependencyManipulator->hasFinalParentConstructor($class)) {
            return null;
        }
        $propertyName = $this->propertyNaming->fqnToVariableName($objectType);
        $this->classDependencyManipulator->addConstructorDependency($class, new PropertyMetadata($propertyName, $objectType));
        return $this->propertyFetchFactory->createFromType($objectType);
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $functionLike
     */
    private function addClassMethodParamForVariable(Variable $variable, ObjectType $objectType, $functionLike): void
    {
        /** @var string $variableName */
        $variableName = $this->nodeNameResolver->getName($variable);
        // add variable to __construct as dependency
        $functionLike->params[] = $this->nodeFactory->createParamFromNameAndType($variableName, $objectType);
    }
}
