<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeDecorator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final class StatementDepthAttributeDecorator
{
    /**
     * @param ClassMethod[] $classMethods
     */
    public static function decorateClassMethods(array $classMethods): void
    {
        foreach ($classMethods as $classMethod) {
            foreach ((array) $classMethod->stmts as $methodStmt) {
                $methodStmt->setAttribute(AttributeKey::IS_FIRST_LEVEL_STATEMENT, \true);
                if ($methodStmt instanceof Expression) {
                    $methodStmt->expr->setAttribute(AttributeKey::IS_FIRST_LEVEL_STATEMENT, \true);
                }
            }
        }
    }
}
