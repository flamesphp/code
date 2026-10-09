<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Entropy\Console\ValueObject;

use FlamesPrefix202610\Entropy\Validation\Assert;
final readonly class ArgumentsAndOptions
{
    /**
     * @param Argument[] $arguments
     * @param Option[] $options
     */
    public function __construct(private array $arguments, private array $options)
    {
        Assert::allIsInstanceOf($this->arguments, Argument::class);
        Assert::allIsInstanceOf($this->options, Option::class);
    }
    /**
     * @return Argument[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
