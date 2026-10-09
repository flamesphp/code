<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\BuilderHelpers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprFalseNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprIntegerNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use Flames\Code\Upgrade\Exception\NotImplementedYetException;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
/**
 * @implements AnnotationToAttributeMapperInterface<ConstExprNode>
 */
final class ConstExprNodeAnnotationToAttributeMapper implements AnnotationToAttributeMapperInterface
{
    /**
     * @param mixed $value
     */
    public function isCandidate($value): bool
    {
        return $value instanceof ConstExprNode;
    }
    /**
     * @param ConstExprNode $value
     */
    public function map($value): Expr
    {
        if ($value instanceof ConstExprIntegerNode) {
            return BuilderHelpers::normalizeValue((int) $value->value);
        }
        if ($value instanceof ConstantFloatType || $value instanceof ConstantBooleanType) {
            return BuilderHelpers::normalizeValue($value->getValue());
        }
        if ($value instanceof ConstExprTrueNode) {
            return BuilderHelpers::normalizeValue(\true);
        }
        if ($value instanceof ConstExprFalseNode) {
            return BuilderHelpers::normalizeValue(\false);
        }
        throw new NotImplementedYetException();
    }
}
