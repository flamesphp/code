<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use function trim;
class ParamLaterInvokedCallableTagValueNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode
{
    use NodeAttributes;
    public function __construct(
        public string $parameterName,
        /** @var string (may be empty) */
        public string $description
    )
    {
    }
    public function __toString(): string
    {
        return trim("{$this->parameterName} {$this->description}");
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['parameterName'], $properties['description']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
