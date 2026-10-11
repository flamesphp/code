<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Type\NeverType;
use Flames\Code\Upgrade\PhpDocDecorator\PhpDocFromTypeDeclarationDecorator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/noreturn_type
 *
 * @see \Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike\DowngradeNeverTypeDeclarationRectorTest
 */
final class DowngradeNeverTypeDeclarationRector extends AbstractRector
{
    public function __construct(private readonly PhpDocFromTypeDeclarationDecorator $phpDocFromTypeDeclarationDecorator)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Function_::class, ClassMethod::class, Closure::class, ArrowFunction::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove "never" return type, add a "@return never" tag instead', [new CodeSample(<<<'CODE_SAMPLE'
function someFunction(): never
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
/**
 * @return never
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
        $neverType = new NeverType();
        if (!$this->phpDocFromTypeDeclarationDecorator->decorateReturnWithSpecificType($node, $neverType)) {
            return null;
        }
        return $node;
    }
}
