<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use Exception;
use PhpParser\Error;
use function array_map;
use function count;
use function implode;
final class ParserErrorsException extends Exception
{
    /**
     * @var Error[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $errors;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $parsedFile;
    /** @var mixed[] */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $attributes;
    /**
     * @param Error[] $errors
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $errors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $parsedFile
    )
    {
        $this->errors = $errors;
        $this->parsedFile = $parsedFile;
        parent::__construct(implode(', ', array_map(static fn(Error $error): string => $error->getRawMessage(), $errors)));
        if (count($errors) > 0) {
            $this->attributes = $errors[0]->getAttributes();
        } else {
            $this->attributes = [];
        }
    }
    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    public function getParsedFile(): ?string
    {
        return $this->parsedFile;
    }
    /**
     * @return mixed[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
