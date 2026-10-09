<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeManipulator;

use PhpParser\Node;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper\UnionTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\ReturnTypeInferer;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
final readonly class AddUnionReturnType
{
    public function __construct(private ReturnTypeInferer $returnTypeInferer, private UnionTypeMapper $unionTypeMapper, private ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard)
    {
    }
    /**
     * @template TCallLike as ClassMethod|Function_
     *
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $node
     * @return TCallLike|null
     */
    public function add($node, Scope $scope)
    {
        if ($node->stmts === null) {
            return null;
        }
        // type is already known
        if ($node->returnType instanceof Node) {
            return null;
        }
        if ($node instanceof ClassMethod && $this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($node, $scope)) {
            return null;
        }
        $inferReturnType = $this->returnTypeInferer->inferFunctionLike($node);
        if (!$inferReturnType instanceof UnionType) {
            return null;
        }
        $returnType = $this->unionTypeMapper->mapToPhpParserNode($inferReturnType, TypeKind::RETURN);
        if (!$returnType instanceof Node) {
            return null;
        }
        // handled by another PHP 7.1 rule with broader scope
        if ($returnType instanceof NullableType) {
            return null;
        }
        $node->returnType = $returnType;
        return $node;
    }
}
