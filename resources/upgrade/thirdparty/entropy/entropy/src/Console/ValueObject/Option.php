<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Entropy\Console\ValueObject;

final class Option
{
    private readonly string $name;
    /**
     * @param string|bool|int|null $defaultValue
     */
    public function __construct(string $name, private readonly string $type, private readonly ?string $description = null, private readonly bool $acceptsMultipleValues = \false, private $defaultValue = null)
    {
        // rename parameter name to -- option name, camelCase to kebab-case conversion
        $this->name = strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    /**
     * @return int|string|bool|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
    public function doesAcceptMultipleValues(): bool
    {
        return $this->acceptsMultipleValues;
    }
    public function getType(): string
    {
        return $this->type;
    }
}
