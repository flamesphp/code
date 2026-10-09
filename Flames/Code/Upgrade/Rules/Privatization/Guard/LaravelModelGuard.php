<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Privatization\Guard;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Enum\LaravelClassName;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\Util\StringUtils;
/**
 * Guards against privatizing Laravel model attributes and scopes
 */
final readonly class LaravelModelGuard
{
    /**
     * @see https://regex101.com/r/Dx0WN5/2
     */
    private const string LARAVEL_MODEL_ATTRIBUTE_REGEX = '#^[gs]et.+Attribute$#';
    /**
     * @see https://regex101.com/r/hxOGeN/2
     */
    private const string LARAVEL_MODEL_SCOPE_REGEX = '#^scope.+$#';
    public function __construct(private PhpAttributeAnalyzer $phpAttributeAnalyzer, private NodeNameResolver $nodeNameResolver, private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function isProtectedMethod(ClassReflection $classReflection, ClassMethod $classMethod): bool
    {
        if (!$classReflection->is(LaravelClassName::MODEL)) {
            return \false;
        }
        $name = (string) $this->nodeNameResolver->getName($classMethod->name);
        if ($this->isAttributeMethod($name, $classMethod)) {
            return \true;
        }
        return $this->isScopeMethod($name, $classMethod);
    }
    private function isAttributeMethod(string $name, ClassMethod $classMethod): bool
    {
        if (StringUtils::isMatch($name, self::LARAVEL_MODEL_ATTRIBUTE_REGEX)) {
            return \true;
        }
        if (!$classMethod->returnType instanceof Node) {
            return \false;
        }
        return $this->nodeTypeResolver->isObjectType($classMethod->returnType, new ObjectType(LaravelClassName::CAST_ATTRIBUTE));
    }
    private function isScopeMethod(string $name, ClassMethod $classMethod): bool
    {
        if (StringUtils::isMatch($name, self::LARAVEL_MODEL_SCOPE_REGEX)) {
            return \true;
        }
        return $this->phpAttributeAnalyzer->hasPhpAttribute($classMethod, LaravelClassName::ATTRIBUTES_SCOPE);
    }
}
