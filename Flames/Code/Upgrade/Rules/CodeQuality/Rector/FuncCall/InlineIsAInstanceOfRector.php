<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\Resolver\ClassNameFromObjectTypeResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\InlineIsAInstanceOfRectorTest
 */
final class InlineIsAInstanceOfRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change `is_a()` with object and class name check to `instanceof`', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run(object $object)
    {
        return is_a($object, SomeType::class);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run(object $object)
    {
        return $object instanceof SomeType;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, 'is_a')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        $firstArgValue = $args[0]->value;
        if (!$this->isFirstObjectType($firstArgValue)) {
            return null;
        }
        /**
         * instanceof with Variable is ok, while on FuncCal with instanceof cause fatal error, see https://3v4l.org/IHb30
         */
        if ($args[1]->value instanceof Variable) {
            return new Instanceof_($firstArgValue, $args[1]->value);
        }
        $className = $this->resolveClassName($args[1]->value);
        if ($className === null) {
            return null;
        }
        return new Instanceof_($firstArgValue, new FullyQualified($className));
    }
    private function resolveClassName(Expr $expr): ?string
    {
        if (!$expr instanceof ClassConstFetch) {
            return null;
        }
        $type = $this->getType($expr);
        if ($type instanceof GenericClassStringType) {
            $type = $type->getGenericType();
        }
        return ClassNameFromObjectTypeResolver::resolve($type);
    }
    private function isFirstObjectType(Expr $expr): bool
    {
        $exprType = $this->getType($expr);
        if ($exprType instanceof ObjectWithoutClassType) {
            return \true;
        }
        return $exprType instanceof ObjectType;
    }
}
