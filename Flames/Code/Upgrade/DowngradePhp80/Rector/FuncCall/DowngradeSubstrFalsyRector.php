<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Empty_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\ParametersAcceptorSelectorVariantsWrapper;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://php.watch/versions/8.0/substr-out-of-bounds
 *
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\FuncCall\DowngradeSubstrFalsyRectorTest
 */
final class DowngradeSubstrFalsyRector extends AbstractRector
{
    private const string IS_FALSY_UNCASTABLE = 'is_falsy_uncastable';
    public function __construct(private readonly ReflectionResolver $reflectionResolver, private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade substr() with cast string on possibly falsy result', [new CodeSample('substr("a", 2);', '(string) substr("a", 2);')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Cast::class, Empty_::class, BooleanNot::class, Ternary::class, Identical::class, Concat::class, MethodCall::class, StaticCall::class, New_::class, AssignOp::class, If_::class, While_::class, Do_::class, ArrayItem::class, ArrayDimFetch::class, BinaryOp::class, FuncCall::class];
    }
    /**
     * @param Cast|Empty_|BooleanNot|Ternary|Identical|Concat|MethodCall|StaticCall|New_|AssignOp|If_|While_|Do_|ArrayItem|ArrayDimFetch|BinaryOp|FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Cast || $node instanceof Empty_ || $node instanceof BooleanNot || $node instanceof AssignOp) {
            $node->expr->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            return null;
        }
        if ($node instanceof Ternary) {
            if (!$node->if instanceof Expr) {
                $node->cond->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            }
            return null;
        }
        if ($node instanceof Concat) {
            $node->left->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            $node->right->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            return null;
        }
        if ($node instanceof Identical) {
            if ($this->valueResolver->isFalse($node->left)) {
                $node->right->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            }
            if ($this->valueResolver->isFalse($node->right)) {
                $node->left->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            }
            return null;
        }
        if ($node instanceof If_ || $node instanceof While_ || $node instanceof Do_) {
            $node->cond->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            return null;
        }
        if ($node instanceof ArrayItem) {
            if ($node->key instanceof Expr) {
                $node->key->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            }
            return null;
        }
        if ($node instanceof ArrayDimFetch) {
            if ($node->dim instanceof Expr) {
                $node->dim->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            }
            return null;
        }
        if ($node instanceof BinaryOp) {
            $node->left->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            $node->right->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
            return null;
        }
        if ($node instanceof CallLike) {
            if ($node->isFirstClassCallable()) {
                return null;
            }
            $reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);
            if (!$reflection instanceof MethodReflection && !$reflection instanceof FunctionReflection) {
                return null;
            }
            $parameterAcceptor = ParametersAcceptorSelectorVariantsWrapper::select($reflection, $node, ScopeFetcher::fetch($node));
            foreach ($parameterAcceptor->getParameters() as $position => $parameterReflection) {
                if ($parameterReflection->getType()->isFalse()->no()) {
                    continue;
                }
                $arg = $node->getArg($parameterReflection->getName(), $position);
                if ($arg instanceof Arg) {
                    $arg->value->setAttribute(self::IS_FALSY_UNCASTABLE, \true);
                }
            }
        }
        if (!$this->isName($node, 'substr')) {
            return null;
        }
        if ($node->getAttribute(self::IS_FALSY_UNCASTABLE) === \true) {
            return null;
        }
        $type = $this->getType($node);
        if ($type->isNonEmptyString()->yes()) {
            return null;
        }
        $offset = $node->getArg('offset', 1);
        if ($offset instanceof Arg) {
            $offsetType = $this->getType($offset->value);
            if ($offsetType instanceof ConstantIntegerType && $offsetType->getValue() <= 0) {
                $length = $node->getArg('length', 2);
                if ($length instanceof Arg) {
                    $lengthType = $this->getType($length->value);
                    if ($lengthType instanceof ConstantIntegerType && $lengthType->getValue() >= 0) {
                        return null;
                    }
                    return new String_($node);
                }
                return null;
            }
        }
        return new String_($node);
    }
}
