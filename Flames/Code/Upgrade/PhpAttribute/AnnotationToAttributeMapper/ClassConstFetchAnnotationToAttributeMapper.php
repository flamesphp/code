<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
use Flames\Code\Upgrade\Validation\RectorAssert;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\InvalidArgumentException;
/**
 * @implements AnnotationToAttributeMapperInterface<string>
 */
final class ClassConstFetchAnnotationToAttributeMapper implements AnnotationToAttributeMapperInterface
{
    /**
     * @param mixed $value
     */
    public function isCandidate($value): bool
    {
        if (!is_string($value)) {
            return \false;
        }
        if (!str_contains($value, '::')) {
            return \false;
        }
        // is quoted? skip it
        return !str_starts_with($value, '"');
    }
    /**
     * @param string $value
     * @return String_|ClassConstFetch
     */
    public function map($value): Node
    {
        $values = explode('::', $value);
        if (count($values) !== 2) {
            return new String_($value);
        }
        [$class, $constant] = $values;
        if ($class === '') {
            return new String_($value);
        }
        try {
            RectorAssert::className(ltrim($class, '\\'));
            RectorAssert::constantName($constant);
        } catch (InvalidArgumentException) {
            return new String_($value);
        }
        return new ClassConstFetch(new Name($class), $constant);
    }
}
