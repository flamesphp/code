<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp82\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\IntersectionType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType;
use Flames\Code\Upgrade\NodeManipulator\PropertyDecorator;
use Flames\Code\Upgrade\PhpDocDecorator\PhpDocFromTypeDeclarationDecorator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @changelog https://php.watch/versions/8.2/dnf-types
 *
 * @see \Flames\Code\Upgrade\DowngradePhp82\Rector\Class_\DowngradeUnionIntersectionRectorTest
 */
final class DowngradeUnionIntersectionRector extends AbstractRector
{
    public function __construct(private readonly PropertyDecorator $propertyDecorator, private readonly PhpDocFromTypeDeclarationDecorator $phpDocFromTypeDeclarationDecorator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove the union type with intersection, use docblock based', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public (A&B)|C $foo;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @var (A&B)|C
     */
    public $foo;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassLike::class, ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }
    /**
     * @param ClassLike|ClassMethod|Function_|Closure|ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof ClassLike) {
            return $this->processFunctionLike($node);
        }
        return $this->processClassLike($node);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $functionLike
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction
     */
    private function processFunctionLike($functionLike)
    {
        $paramDecorated = \false;
        foreach ($functionLike->getParams() as $param) {
            if (!$this->isUnionIntersection($param->type)) {
                continue;
            }
            Assert::isInstanceOf($param->type, UnionType::class);
            $this->phpDocFromTypeDeclarationDecorator->decorateParam($param, $functionLike, [\PHPStan\Type\UnionType::class]);
            $paramDecorated = \true;
        }
        if (!$this->isUnionIntersection($functionLike->returnType)) {
            if ($paramDecorated) {
                return $functionLike;
            }
            return null;
        }
        Assert::isInstanceOf($functionLike->returnType, UnionType::class);
        $this->phpDocFromTypeDeclarationDecorator->decorateReturn($functionLike);
        return $functionLike;
    }
    private function processClassLike(ClassLike $classLike): ?ClassLike
    {
        $hasChanged = \false;
        foreach ($classLike->getProperties() as $property) {
            if (!$this->isUnionIntersection($property->type)) {
                continue;
            }
            Assert::isInstanceOf($property->type, UnionType::class);
            $this->propertyDecorator->decorateWithDocBlock($property, $property->type);
            $property->type = null;
            $hasChanged = \true;
        }
        return $hasChanged ? $classLike : null;
    }
    private function isUnionIntersection(?Node $node): bool
    {
        if (!$node instanceof UnionType) {
            return \false;
        }
        $found = array_any($node->types, fn($type) => $type instanceof IntersectionType);
        return $found;
    }
}
