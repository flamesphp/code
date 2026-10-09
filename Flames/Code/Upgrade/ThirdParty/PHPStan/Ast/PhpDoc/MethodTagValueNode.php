<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use function count;
use function implode;
class MethodTagValueNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode
{
    use NodeAttributes;
    /**
     * @param MethodTagValueParameterNode[] $parameters
     * @param TemplateTagValueNode[] $templateTypes
     */
    public function __construct(
        public bool $isStatic,
        public ?TypeNode $returnType,
        public string $methodName,
        public array $parameters,
        /** @var string (may be empty) */
        public string $description,
        public array $templateTypes
    )
    {
    }
    public function __toString(): string
    {
        $static = $this->isStatic ? 'static ' : '';
        $returnType = $this->returnType !== null ? "{$this->returnType} " : '';
        $parameters = implode(', ', $this->parameters);
        $description = $this->description !== '' ? " {$this->description}" : '';
        $templateTypes = count($this->templateTypes) > 0 ? '<' . implode(', ', $this->templateTypes) . '>' : '';
        return "{$static}{$returnType}{$this->methodName}{$templateTypes}({$parameters}){$description}";
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['isStatic'], $properties['returnType'], $properties['methodName'], $properties['parameters'], $properties['description'], $properties['templateTypes']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
