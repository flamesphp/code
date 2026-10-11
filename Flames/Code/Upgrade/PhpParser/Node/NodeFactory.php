<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Builder\Method;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Builder\Param as ParamBuilder;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Builder\Property as PropertyBuilder;
use Flames\Code\Upgrade\ThirdParty\PhpParser\BuilderFactory;
use Flames\Code\Upgrade\ThirdParty\PhpParser\BuilderHelpers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\DeclareItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Clone_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafePropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryMinus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\UnaryPlus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Declare_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\NodeDecorator\PropertyTypeDecorator;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PostRector\ValueObject\PropertyMetadata;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
/**
 * @see \Flames\Code\Upgrade\Tests\Flames\Code\Upgrade\PhpParser\Node\NodeFactoryTest
 */
final readonly class NodeFactory
{
    private const string THIS = 'this';
    public function __construct(private BuilderFactory $builderFactory, private PhpDocInfoFactory $phpDocInfoFactory, private StaticTypeMapper $staticTypeMapper, private PropertyTypeDecorator $propertyTypeDecorator, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private PhpVersionProvider $phpVersionProvider)
    {
    }
    /**
     * @param string|ObjectReference::* $className
     * Creates "\SomeClass::CONSTANT"
     */
    public function createClassConstFetch(string $className, string $constantName): ClassConstFetch
    {
        $name = $this->createName($className);
        return $this->createClassConstFetchFromName($name, $constantName);
    }
    /**
     * @param string|ObjectReference::* $className
     * Creates "\SomeClass::class"
     */
    public function createClassConstReference(string $className): ClassConstFetch
    {
        return $this->createClassConstFetch($className, 'class');
    }
    /**
     * Creates "['item', $variable]"
     *
     * @param mixed[] $items
     */
    public function createArray(array $items): Array_
    {
        $arrayItems = [];
        $defaultKey = 0;
        foreach ($items as $key => $item) {
            $customKey = $key !== $defaultKey ? $key : null;
            $arrayItems[] = $this->createArrayItem($item, $customKey);
            ++$defaultKey;
        }
        return new Array_($arrayItems);
    }
    /**
     * Creates "($args)"
     *
     * @param mixed[] $values
     * @return Arg[]
     */
    public function createArgs(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($value instanceof ArrayItem) {
                $values[$key] = $value->value;
            }
        }
        return $this->builderFactory->args($values);
    }
    public function createDeclaresStrictType(): Declare_
    {
        $declareItem = new DeclareItem(new Identifier('strict_types'), new Int_(1));
        return new Declare_([$declareItem]);
    }
    /**
     * Creates $this->property = $property;
     */
    public function createPropertyAssignment(string $propertyName): Assign
    {
        $variable = new Variable($propertyName);
        return $this->createPropertyAssignmentWithExpr($propertyName, $variable);
    }
    /**
     * @api
     */
    public function createPropertyAssignmentWithExpr(string $propertyName, Expr $expr): Assign
    {
        $propertyFetch = $this->createPropertyFetch(self::THIS, $propertyName);
        return new Assign($propertyFetch, $expr);
    }
    /**
     * @param mixed $argument
     */
    public function createArg($argument): Arg
    {
        return new Arg(BuilderHelpers::normalizeValue($argument));
    }
    public function createPublicMethod(string $name): ClassMethod
    {
        $method = new Method($name);
        $method->makePublic();
        return $method->getNode();
    }
    public function createParamFromNameAndType(string $name, ?Type $type): Param
    {
        $param = new ParamBuilder($name);
        if ($type instanceof Type) {
            $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::PARAM);
            if ($typeNode instanceof Node) {
                $param->setType($typeNode);
            }
        }
        return $param->getNode();
    }
    public function createPrivatePropertyFromNameAndType(string $name, ?Type $type): Property
    {
        $propertyBuilder = new PropertyBuilder($name);
        $propertyBuilder->makePrivate();
        $property = $propertyBuilder->getNode();
        $this->propertyTypeDecorator->decorate($property, $type);
        return $property;
    }
    /**
     * @api symfony
     * @param mixed[] $arguments
     */
    public function createLocalMethodCall(string $method, array $arguments = []): MethodCall
    {
        $variable = new Variable('this');
        return $this->createMethodCall($variable, $method, $arguments);
    }
    /**
     * @api symfony, doctrine, phpunit
     * @param mixed[] $arguments
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|string $exprOrVariableName
     */
    public function createMethodCall($exprOrVariableName, string $method, array $arguments = []): MethodCall
    {
        $callerExpr = $this->createMethodCaller($exprOrVariableName);
        return $this->builderFactory->methodCall($callerExpr, $method, $arguments);
    }
    /**
     * @param string|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $variableNameOrExpr
     */
    public function createPropertyFetch($variableNameOrExpr, string $property): PropertyFetch
    {
        $fetcherExpr = is_string($variableNameOrExpr) ? new Variable($variableNameOrExpr) : $variableNameOrExpr;
        return $this->builderFactory->propertyFetch($fetcherExpr, $property);
    }
    /**
     * @api doctrine
     */
    public function createPrivateProperty(string $name): Property
    {
        $propertyBuilder = new PropertyBuilder($name);
        $propertyBuilder->makePrivate();
        $property = $propertyBuilder->getNode();
        $this->phpDocInfoFactory->createFromNode($property);
        return $property;
    }
    /**
     * @param Expr[] $exprs
     */
    public function createConcat(array $exprs): ?Concat
    {
        if (count($exprs) < 2) {
            return null;
        }
        $previousConcat = array_shift($exprs);
        foreach ($exprs as $expr) {
            $previousConcat = new Concat($previousConcat, $expr);
        }
        if (!$previousConcat instanceof Concat) {
            throw new ShouldNotHappenException();
        }
        return $previousConcat;
    }
    /**
     * @param string|ObjectReference::* $class
     * @param Node[] $args
     */
    public function createStaticCall(string $class, string $method, array $args = []): StaticCall
    {
        $name = $this->createName($class);
        $args = $this->createArgs($args);
        return new StaticCall($name, $method, $args);
    }
    /**
     * @param mixed[] $arguments
     */
    public function createFuncCall(string $name, array $arguments = []): FuncCall
    {
        $arguments = $this->createArgs($arguments);
        return new FuncCall(new Name($name), $arguments);
    }
    public function createSelfFetchConstant(string $constantName): ClassConstFetch
    {
        $name = new Name(ObjectReference::SELF);
        return new ClassConstFetch($name, $constantName);
    }
    public function createNull(): ConstFetch
    {
        return new ConstFetch(new Name('null'));
    }
    public function createPromotedPropertyParam(PropertyMetadata $propertyMetadata): Param
    {
        $paramBuilder = new ParamBuilder($propertyMetadata->getName());
        $propertyType = $propertyMetadata->getType();
        if ($propertyType instanceof Type) {
            $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($propertyType, TypeKind::PROPERTY);
            if ($typeNode instanceof Node) {
                $paramBuilder->setType($typeNode);
            }
        }
        $param = $paramBuilder->getNode();
        $propertyFlags = $propertyMetadata->getFlags();
        $param->flags = $propertyFlags !== 0 ? $propertyFlags : Modifiers::PRIVATE;
        // make readonly by default
        if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::READONLY_PROPERTY)) {
            $param->flags |= Modifiers::READONLY;
        }
        return $param;
    }
    public function createFalse(): ConstFetch
    {
        return new ConstFetch(new Name('false'));
    }
    public function createTrue(): ConstFetch
    {
        return new ConstFetch(new Name('true'));
    }
    /**
     * @api phpunit
     * @param string|ObjectReference::* $constantName
     */
    public function createClassConstFetchFromName(Name $className, string $constantName): ClassConstFetch
    {
        return $this->builderFactory->classConstFetch($className, $constantName);
    }
    /**
     * @param array<NotIdentical|BooleanAnd|BooleanOr|Identical> $newNodes
     */
    public function createReturnBooleanAnd(array $newNodes): ?Expr
    {
        if ($newNodes === []) {
            return null;
        }
        if (count($newNodes) === 1) {
            return $newNodes[0];
        }
        return $this->createBooleanAndFromNodes($newNodes);
    }
    /**
     * Setting all child nodes to null is needed to avoid reprint of invalid tokens
     * @see https://github.com/rectorphp/rector/issues/8712
     *
     * @template TNode as Node
     *
     * @param TNode $node
     * @return TNode
     */
    public function createReprintedNode(Node $node): Node
    {
        // reset original node, to allow the printer to re-use the node
        $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($node, static function (Node $subNode): Node {
            $subNode->setAttribute(AttributeKey::ORIGINAL_NODE, null);
            return $subNode;
        });
        return $node;
    }
    /**
     * @param string|int|null $key
     * @param mixed $item
     */
    private function createArrayItem($item, $key = null): ArrayItem
    {
        $arrayItem = null;
        if ($item instanceof Variable || $item instanceof MethodCall || $item instanceof StaticCall || $item instanceof FuncCall || $item instanceof Concat || $item instanceof Scalar || $item instanceof Cast || $item instanceof ConstFetch || $item instanceof PropertyFetch || $item instanceof StaticPropertyFetch || $item instanceof NullsafePropertyFetch || $item instanceof NullsafeMethodCall || $item instanceof Clone_ || $item instanceof Instanceof_) {
            $arrayItem = new ArrayItem($item);
        } elseif ($item instanceof Identifier) {
            $string = new String_($item->toString());
            $arrayItem = new ArrayItem($string);
        } elseif (is_scalar($item) || $item instanceof Array_) {
            $itemValue = BuilderHelpers::normalizeValue($item);
            $arrayItem = new ArrayItem($itemValue);
        } elseif (is_array($item)) {
            $arrayItem = new ArrayItem($this->createArray($item));
        } elseif ($item === null || $item instanceof ClassConstFetch) {
            $itemValue = BuilderHelpers::normalizeValue($item);
            $arrayItem = new ArrayItem($itemValue);
        } elseif ($item instanceof Arg) {
            $arrayItem = new ArrayItem($item->value);
        }
        if ($arrayItem instanceof ArrayItem) {
            $this->decorateArrayItemWithKey($key, $arrayItem);
            return $arrayItem;
        }
        if ($item instanceof New_) {
            $arrayItem = new ArrayItem($item);
            $this->decorateArrayItemWithKey($key, $arrayItem);
            return $arrayItem;
        }
        if ($item instanceof UnaryPlus || $item instanceof UnaryMinus) {
            $arrayItem = new ArrayItem($item);
            $this->decorateArrayItemWithKey($key, $arrayItem);
            return $arrayItem;
        }
        // fallback to other nodes
        if ($item instanceof Expr) {
            $arrayItem = new ArrayItem($item);
            $this->decorateArrayItemWithKey($key, $arrayItem);
            return $arrayItem;
        }
        $itemValue = BuilderHelpers::normalizeValue($item);
        $arrayItem = new ArrayItem($itemValue);
        $this->decorateArrayItemWithKey($key, $arrayItem);
        return $arrayItem;
    }
    /**
     * @param int|string|null $key
     */
    private function decorateArrayItemWithKey($key, ArrayItem $arrayItem): void
    {
        if ($key === null) {
            return;
        }
        $arrayItem->key = BuilderHelpers::normalizeValue($key);
    }
    /**
     * @param Expr\BinaryOp[] $binaryOps
     */
    private function createBooleanAndFromNodes(array $binaryOps): BooleanAnd
    {
        /** @var NotIdentical|BooleanAnd $mainBooleanAnd */
        $mainBooleanAnd = array_shift($binaryOps);
        foreach ($binaryOps as $binaryOp) {
            $mainBooleanAnd = new BooleanAnd($mainBooleanAnd, $binaryOp);
        }
        /** @var BooleanAnd $mainBooleanAnd */
        return $mainBooleanAnd;
    }
    /**
     * @param string|ObjectReference::* $className
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified
     */
    private function createName(string $className)
    {
        if (in_array($className, [ObjectReference::PARENT, ObjectReference::SELF, ObjectReference::STATIC], \true)) {
            return new Name($className);
        }
        return new FullyQualified($className);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|string $exprOrVariableName
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr
     */
    private function createMethodCaller($exprOrVariableName)
    {
        if (is_string($exprOrVariableName)) {
            return new Variable($exprOrVariableName);
        }
        if ($exprOrVariableName instanceof PropertyFetch) {
            return new PropertyFetch($exprOrVariableName->var, $exprOrVariableName->name);
        }
        if ($exprOrVariableName instanceof StaticPropertyFetch) {
            return new StaticPropertyFetch($exprOrVariableName->class, $exprOrVariableName->name);
        }
        if ($exprOrVariableName instanceof MethodCall) {
            return new MethodCall($exprOrVariableName->var, $exprOrVariableName->name, $exprOrVariableName->args);
        }
        return $exprOrVariableName;
    }
}
