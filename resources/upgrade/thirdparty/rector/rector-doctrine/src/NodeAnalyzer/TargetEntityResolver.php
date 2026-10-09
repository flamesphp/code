<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\NodeAnalyzer;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Doctrine\CodeQuality\Enum\DocumentMappingKey;
use Flames\Code\Upgrade\Doctrine\CodeQuality\Enum\EntityMappingKey;
use Flames\Code\Upgrade\Exception\NotImplementedYetException;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class TargetEntityResolver
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private ReflectionProvider $reflectionProvider)
    {
    }
    public function resolveFromAttribute(Attribute $attribute): ?string
    {
        foreach ($attribute->args as $arg) {
            if (!$arg->name instanceof Identifier) {
                continue;
            }
            if (!in_array($arg->name->toString(), [EntityMappingKey::TARGET_ENTITY, DocumentMappingKey::TARGET_DOCUMENT], \true)) {
                continue;
            }
            return $this->resolveFromExpr($arg->value);
        }
        return null;
    }
    public function resolveFromExpr(Expr $targetEntityExpr): ?string
    {
        if ($targetEntityExpr instanceof ClassConstFetch) {
            $targetEntity = (string) $this->nodeNameResolver->getName($targetEntityExpr->class);
            if (!$this->reflectionProvider->hasClass($targetEntity)) {
                return null;
            }
            return $targetEntity;
        }
        if ($targetEntityExpr instanceof String_) {
            $targetEntity = $targetEntityExpr->value;
            if (!$this->reflectionProvider->hasClass($targetEntity)) {
                return null;
            }
            return $targetEntity;
        }
        $errorMessage = sprintf('Add support for "%s" targetEntity in "%s"', $targetEntityExpr::class, self::class);
        throw new NotImplementedYetException($errorMessage);
    }
}
