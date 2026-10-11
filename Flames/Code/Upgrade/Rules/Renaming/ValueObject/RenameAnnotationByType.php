<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\Renaming\Contract\RenameAnnotationInterface;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class RenameAnnotationByType implements RenameAnnotationInterface
{
    public function __construct(private string $type, private string $oldAnnotation, private string $newAnnotation)
    {
        RectorAssert::className($this->type);
    }
    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->type);
    }
    public function getOldAnnotation(): string
    {
        return $this->oldAnnotation;
    }
    public function getNewAnnotation(): string
    {
        return $this->newAnnotation;
    }
}
