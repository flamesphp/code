<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp72\Rector\FunctionLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Type\ObjectWithoutClassType;
use Flames\Code\Upgrade\PhpDocDecorator\PhpDocFromTypeDeclarationDecorator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp72\Rector\FunctionLike\DowngradeObjectTypeDeclarationRectorTest
 */
final class DowngradeObjectTypeDeclarationRector extends AbstractRector
{
    public function __construct(private readonly PhpDocFromTypeDeclarationDecorator $phpDocFromTypeDeclarationDecorator)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Function_::class, ClassMethod::class, Closure::class];
    }
    /**
     * @param Function_|ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        $objectWithoutClassType = new ObjectWithoutClassType();
        $hasChanged = \false;
        $hasParamChanged = \false;
        foreach ($node->params as $param) {
            $hasParamChanged = $this->phpDocFromTypeDeclarationDecorator->decorateParamWithSpecificType($param, $node, $objectWithoutClassType);
            if ($hasParamChanged) {
                $hasChanged = \true;
            }
        }
        if (!$this->phpDocFromTypeDeclarationDecorator->decorateReturnWithSpecificType($node, $objectWithoutClassType)) {
            if ($hasChanged) {
                return $node;
            }
            return null;
        }
        return $node;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove the "object" param and return type, add a @param and @return tags instead', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function someFunction(object $someObject): object
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @param object $someObject
     * @return object
     */
    public function someFunction($someObject)
    {
    }
}
CODE_SAMPLE
)]);
    }
}
