<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeFinder;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ExtendsTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\ArrayTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\ChildDoctrineRepositoryClassTypeRectorTest
 */
final class ChildDoctrineRepositoryClassTypeRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly NodeFinder $nodeFinder, private readonly DocBlockUpdater $docBlockUpdater)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add return type to classes that extend Doctrine\ORM\EntityRepository based on return Doctrine method names', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<SomeType>
 */
final class SomeRepository extends EntityRepository
{
    public function getActiveItem()
    {
        return $this->findOneBy([
            'something'
        ]);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<SomeType>
 */
final class SomeRepository extends EntityRepository
{
    public function getActiveItem(): ?SomeType
    {
        return $this->findOneBy([
            'something'
        ]);
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, new ObjectType('Doctrine\ORM\EntityRepository'))) {
            return null;
        }
        $entityClassName = $this->resolveEntityClassnameFromPhpDoc($node);
        if ($entityClassName === null) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if ($this->shouldSkipClassMethod($classMethod)) {
                continue;
            }
            if ($this->containsMethodCallNamed($classMethod, 'getOneOrNullResult')) {
                $classMethod->returnType = $this->createNullableType($entityClassName);
            } elseif ($this->containsMethodCallNamed($classMethod, 'findOneBy')) {
                $classMethod->returnType = $this->createNullableType($entityClassName);
            }
            if ($this->containsMethodCallNamed($classMethod, 'findBy')) {
                $classMethod->returnType = new Identifier('array');
                // add docblock with type
                $classMethodPhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
                $arrayTypeNode = new ArrayTypeNode(new IdentifierTypeNode($entityClassName));
                $classMethodPhpDocInfo->addTagValueNode(new ReturnTagValueNode($arrayTypeNode, ''));
                $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($classMethod);
            }
            $hasChanged = \true;
            // try to figure out the return type
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function resolveEntityClassnameFromPhpDoc(Class_ $class): ?string
    {
        $classPhpDocInfo = $this->phpDocInfoFactory->createFromNode($class);
        // we need a way to resolve entity type... 1st idea is from @extends docblock
        if (!$classPhpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $extendsTagValuePhpDocNodes = $classPhpDocInfo->getTagsByName('extends');
        if ($extendsTagValuePhpDocNodes === []) {
            return null;
        }
        $extendsTagValueNode = $extendsTagValuePhpDocNodes[0]->value;
        if (!$extendsTagValueNode instanceof ExtendsTagValueNode) {
            return null;
        }
        $genericTypeNode = $extendsTagValueNode->type;
        if ($genericTypeNode->type->name !== 'EntityRepository') {
            return null;
        }
        $entityGenericType = $genericTypeNode->genericTypes[0];
        if (!$entityGenericType instanceof IdentifierTypeNode) {
            return null;
        }
        // skip if value is used in generics
        if (in_array($entityGenericType->name, $classPhpDocInfo->getTemplateNames(), \true)) {
            return null;
        }
        return $entityGenericType->name;
    }
    private function containsMethodCallNamed(ClassMethod $classMethod, string $desiredMethodName): bool
    {
        return (bool) $this->nodeFinder->findFirst((array) $classMethod->stmts, static function (Node $node) use ($desiredMethodName): bool {
            if (!$node instanceof MethodCall) {
                return \false;
            }
            if (!$node->name instanceof Identifier) {
                return \false;
            }
            $currentMethodCallName = $node->name->toString();
            return $currentMethodCallName === $desiredMethodName;
        });
    }
    private function shouldSkipClassMethod(ClassMethod $classMethod): bool
    {
        if (!$classMethod->isPublic()) {
            return \true;
        }
        if ($classMethod->isStatic()) {
            return \true;
        }
        return $classMethod->returnType instanceof Node;
    }
    private function createNullableType(string $entityClassName): NullableType
    {
        $name = new Name($entityClassName);
        return new NullableType($name);
    }
}
