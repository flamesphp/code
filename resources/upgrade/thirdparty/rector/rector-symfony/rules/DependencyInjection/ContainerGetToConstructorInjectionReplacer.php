<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\DependencyInjection;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Rules\Naming\Naming\PropertyNaming;
use Flames\Code\Upgrade\NodeManipulator\ClassDependencyManipulator;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
use Flames\Code\Upgrade\PostRector\ValueObject\PropertyMetadata;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
final readonly class ContainerGetToConstructorInjectionReplacer
{
    public function __construct(private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private \Flames\Code\Upgrade\Symfony\DependencyInjection\ThisGetTypeMatcher $thisGetTypeMatcher, private PropertyNaming $propertyNaming, private ClassDependencyManipulator $classDependencyManipulator, private NodeFactory $nodeFactory)
    {
    }
    /**
     * Turns `$this->get(SomeType::class)` calls into constructor-injected property fetches.
     * Returns true when at least one dependency was injected.
     */
    public function replace(Class_ $class): bool
    {
        $propertyMetadatas = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($class, function (Node $node) use (&$propertyMetadatas): ?Node {
            if (!$node instanceof MethodCall) {
                return null;
            }
            $className = $this->thisGetTypeMatcher->match($node);
            if (!is_string($className)) {
                return null;
            }
            $propertyName = $this->propertyNaming->fqnToVariableName($className);
            $propertyMetadata = new PropertyMetadata($propertyName, new FullyQualifiedObjectType($className));
            $propertyMetadatas[] = $propertyMetadata;
            return $this->nodeFactory->createPropertyFetch('this', $propertyMetadata->getName());
        });
        if ($propertyMetadatas === []) {
            return \false;
        }
        foreach ($propertyMetadatas as $propertyMetadata) {
            $this->classDependencyManipulator->addConstructorDependency($class, $propertyMetadata);
        }
        return \true;
    }
}
