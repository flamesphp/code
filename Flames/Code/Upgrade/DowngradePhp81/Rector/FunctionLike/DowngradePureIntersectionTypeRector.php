<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\IntersectionType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\PhpDocDecorator\PhpDocFromTypeDeclarationDecorator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/pure-intersection-types
 *
 * @see \Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike\DowngradePureIntersectionTypeRectorTest
 */
final class DowngradePureIntersectionTypeRector extends AbstractRector
{
    public function __construct(private readonly PhpDocFromTypeDeclarationDecorator $phpDocFromTypeDeclarationDecorator)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ArrowFunction::class, ClassMethod::class, Closure::class, Function_::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove the intersection type params and returns, add @param/@return tags instead', [new CodeSample(<<<'CODE_SAMPLE'
function someFunction(): Foo&Bar
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
/**
 * @return Foo&Bar
 */
function someFunction()
{
}
CODE_SAMPLE
)]);
    }
    /**
     * @param ArrowFunction|ClassMethod|Closure|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $paramDecorated = \false;
        foreach ($node->getParams() as $param) {
            if (!$param->type instanceof IntersectionType) {
                continue;
            }
            $this->phpDocFromTypeDeclarationDecorator->decorateParam($param, $node, [\PHPStan\Type\IntersectionType::class]);
            $paramDecorated = \true;
        }
        if (!$node->returnType instanceof IntersectionType) {
            if ($paramDecorated) {
                return $node;
            }
            return null;
        }
        $this->phpDocFromTypeDeclarationDecorator->decorateReturn($node);
        return $node;
    }
}
