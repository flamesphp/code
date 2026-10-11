<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\ValueObject;

use Flames\Code\Upgrade\Rules\Renaming\Contract\RenameAnnotationInterface;
/**
 * @api
 */
final readonly class RenameAnnotation implements RenameAnnotationInterface
{
    public function __construct(private string $oldAnnotation, private string $newAnnotation)
    {
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
