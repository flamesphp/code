<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
/**
 * @api used in doctrine
 */
final readonly class DoctrineEntityAnalyzer
{
    /**
     * @var string[]
     */
    private const array DOCTRINE_MAPPING_CLASSES = ['Doctrine\ORM\Mapping\Entity', 'Doctrine\ORM\Mapping\Embeddable', 'Doctrine\ODM\MongoDB\Mapping\Annotations\Document', 'Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument'];
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function hasClassAnnotation(Class_ $class): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($class);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return \false;
        }
        return $phpDocInfo->hasByAnnotationClasses(self::DOCTRINE_MAPPING_CLASSES);
    }
    public function hasClassReflectionAttribute(ClassReflection $classReflection): bool
    {
        /** @var ReflectionClass $nativeReflectionClass */
        $nativeReflectionClass = $classReflection->getNativeReflection();
        // skip early in case of no attributes at all
        if ((method_exists($nativeReflectionClass, 'getAttributes') ? $nativeReflectionClass->getAttributes() : []) === []) {
            return \false;
        }
        $found = array_any(self::DOCTRINE_MAPPING_CLASSES, fn($doctrineMappingClass) => (method_exists($nativeReflectionClass, 'getAttributes') ? $nativeReflectionClass->getAttributes($doctrineMappingClass) : []) !== []);
        return $found;
    }
}
