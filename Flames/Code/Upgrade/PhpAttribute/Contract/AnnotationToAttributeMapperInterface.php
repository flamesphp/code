<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\Contract;

use PhpParser\Node;
/**
 * @template T as mixed
 */
interface AnnotationToAttributeMapperInterface
{
    /**
     * @param mixed $value
     */
    public function isCandidate($value): bool;
    /**
     * @param T $value
     */
    public function map($value): Node;
}
