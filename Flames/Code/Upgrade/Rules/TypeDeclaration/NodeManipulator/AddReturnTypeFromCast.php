<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\ReturnTypeInferer;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
final readonly class AddReturnTypeFromCast
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private ReturnTypeInferer $returnTypeInferer, private StaticTypeMapper $staticTypeMapper, private ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|null
     */
    public function add($functionLike, Scope $scope)
    {
        if ($functionLike->returnType instanceof Node) {
            return null;
        }
        if ($functionLike instanceof ClassMethod && $this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($functionLike, $scope)) {
            return null;
        }
        $hasNonCastReturn = (bool) $this->betterNodeFinder->findFirstInFunctionLikeScoped($functionLike, static fn(Node $subNode): bool => $subNode instanceof Return_ && !$subNode->expr instanceof Cast);
        if ($hasNonCastReturn) {
            return null;
        }
        $returnType = $this->returnTypeInferer->inferFunctionLike($functionLike);
        if ($returnType instanceof UnionType) {
            return null;
        }
        $returnTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($returnType, TypeKind::RETURN);
        if (!$returnTypeNode instanceof Node) {
            return null;
        }
        $functionLike->returnType = $returnTypeNode;
        return $functionLike;
    }
}
