<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Arguments\NodeAnalyzer;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use Flames\Code\Upgrade\Rules\Arguments\ValueObject\ArgumentAdder;
use Flames\Code\Upgrade\Rules\Arguments\ValueObject\ArgumentAdderWithoutDefaultValue;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ArgumentAddingScope
{
    /**
     * @api
     */
    public const string SCOPE_PARENT_CALL = 'parent_call';
    /**
     * @api
     */
    public const string SCOPE_METHOD_CALL = 'method_call';
    /**
     * @api
     */
    public const string SCOPE_CLASS_METHOD = 'class_method';
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall $expr
     * @param \Flames\Code\Upgrade\Rules\Arguments\ValueObject\ArgumentAdder|\Flames\Code\Upgrade\Rules\Arguments\ValueObject\ArgumentAdderWithoutDefaultValue $argumentAdder
     */
    public function isInCorrectScope($expr, $argumentAdder): bool
    {
        if ($argumentAdder->getScope() === null) {
            return \true;
        }
        $scope = $argumentAdder->getScope();
        if ($expr instanceof StaticCall) {
            if (!$expr->class instanceof Name) {
                return \false;
            }
            if ($this->nodeNameResolver->isName($expr->class, ObjectReference::PARENT)) {
                return $scope === self::SCOPE_PARENT_CALL;
            }
            return $scope === self::SCOPE_METHOD_CALL;
        }
        // MethodCall
        return $scope === self::SCOPE_METHOD_CALL;
    }
}
