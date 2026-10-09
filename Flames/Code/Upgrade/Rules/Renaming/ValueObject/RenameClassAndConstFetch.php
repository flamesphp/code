<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\Renaming\Contract\RenameClassConstFetchInterface;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class RenameClassAndConstFetch implements RenameClassConstFetchInterface
{
    public function __construct(private string $oldClass, private string $oldConstant, private string $newClass, private string $newConstant)
    {
        RectorAssert::className($this->oldClass);
        RectorAssert::constantName($this->oldConstant);
        RectorAssert::className($this->newClass);
        RectorAssert::constantName($this->newConstant);
    }
    public function getOldObjectType(): ObjectType
    {
        return new ObjectType($this->oldClass);
    }
    public function getOldConstant(): string
    {
        return $this->oldConstant;
    }
    public function getNewConstant(): string
    {
        return $this->newConstant;
    }
    public function getNewClass(): string
    {
        return $this->newClass;
    }
}
