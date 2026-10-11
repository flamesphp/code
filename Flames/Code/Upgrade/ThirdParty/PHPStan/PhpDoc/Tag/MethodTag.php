<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/**
 * @api
 */
final class MethodTag
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $returnType;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isStatic;
    /**
     * @var array<string, MethodTagParameter>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $parameters;
    /**
     * @var array<string, TemplateTag>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $templateTags;
    /**
     * @param array<string, MethodTagParameter> $parameters
     * @param array<string, TemplateTag> $templateTags
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $returnType,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isStatic,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $parameters,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $templateTags
    )
    {
        $this->returnType = $returnType;
        $this->isStatic = $isStatic;
        $this->parameters = $parameters;
        $this->templateTags = $templateTags;
    }
    public function getReturnType(): Type
    {
        return $this->returnType;
    }
    public function isStatic(): bool
    {
        return $this->isStatic;
    }
    /**
     * @return array<string, MethodTagParameter>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    /**
     * @return array<string, TemplateTag>
     */
    public function getTemplateTags(): array
    {
        return $this->templateTags;
    }
}
