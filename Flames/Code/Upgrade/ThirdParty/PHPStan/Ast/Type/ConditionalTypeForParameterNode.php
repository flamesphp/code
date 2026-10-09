<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use function sprintf;
class ConditionalTypeForParameterNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode
{
    use NodeAttributes;
    public function __construct(public string $parameterName, public \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode $targetType, public \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode $if, public \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode $else, public bool $negated)
    {
    }
    public function __toString(): string
    {
        return sprintf('(%s %s %s ? %s : %s)', $this->parameterName, $this->negated ? 'is not' : 'is', $this->targetType, $this->if, $this->else);
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['parameterName'], $properties['targetType'], $properties['if'], $properties['else'], $properties['negated']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
