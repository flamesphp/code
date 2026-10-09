<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator;

use FlamesPrefix202610\Nette\Utils\Strings;
use PhpParser\Node;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocParser\ClassAnnotationMatcher;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation\CurlyListNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\Enum\ClassName;
use Flames\Code\Upgrade\Rules\Renaming\Collector\RenamedNameCollector;
final readonly class PhpDocClassRenamer
{
    public function __construct(private ClassAnnotationMatcher $classAnnotationMatcher, private RenamedNameCollector $renamedNameCollector)
    {
    }
    /**
     * Covers annotations like @ORM, @Serializer, @Assert etc
     * See https://github.com/rectorphp/rector/issues/1872
     *
     * @param string[] $oldToNewClasses
     */
    public function changeTypeInAnnotationTypes(Node $node, PhpDocInfo $phpDocInfo, array $oldToNewClasses, bool &$hasChanged): bool
    {
        $this->processAssertChoiceTagValueNode($oldToNewClasses, $phpDocInfo, $hasChanged);
        $this->processDoctrineRelationTagValueNode($node, $oldToNewClasses, $phpDocInfo, $hasChanged);
        $this->processSerializerTypeTagValueNode($oldToNewClasses, $phpDocInfo, $hasChanged);
        return $hasChanged;
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    private function processAssertChoiceTagValueNode(array $oldToNewClasses, PhpDocInfo $phpDocInfo, bool &$hasChanged): void
    {
        $assertChoiceDoctrineAnnotationTagValueNode = $phpDocInfo->findOneByAnnotationClass('Symfony\Component\Validator\Constraints\Choice');
        if (!$assertChoiceDoctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return;
        }
        $callbackArrayItemNode = $assertChoiceDoctrineAnnotationTagValueNode->getValue('callback');
        if (!$callbackArrayItemNode instanceof ArrayItemNode) {
            return;
        }
        $callbackClass = $callbackArrayItemNode->value;
        // array is needed for callable
        if (!$callbackClass instanceof CurlyListNode) {
            return;
        }
        $callableCallbackArrayItems = $callbackClass->getValues();
        $classNameArrayItemNode = $callableCallbackArrayItems[0];
        $classNameStringNode = $classNameArrayItemNode->value;
        if (!$classNameStringNode instanceof StringNode) {
            return;
        }
        foreach ($oldToNewClasses as $oldClass => $newClass) {
            if ($classNameStringNode->value !== $oldClass) {
                continue;
            }
            $this->renamedNameCollector->add($oldClass);
            $classNameStringNode->value = $newClass;
            // trigger reprint
            $classNameArrayItemNode->setAttribute(PhpDocAttributeKey::ORIG_NODE, null);
            $hasChanged = \true;
            break;
        }
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    private function processDoctrineRelationTagValueNode(Node $node, array $oldToNewClasses, PhpDocInfo $phpDocInfo, bool &$hasChanged): void
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClasses(['Doctrine\ORM\Mapping\OneToMany', 'Doctrine\ORM\Mapping\ManyToMany', 'Doctrine\ORM\Mapping\Embedded']);
        if (!$doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return;
        }
        $this->processDoctrineToMany($doctrineAnnotationTagValueNode, $node, $oldToNewClasses, $hasChanged);
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    private function processSerializerTypeTagValueNode(array $oldToNewClasses, PhpDocInfo $phpDocInfo, bool &$hasChanged): void
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->findOneByAnnotationClass(ClassName::JMS_TYPE);
        if (!$doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return;
        }
        $classNameArrayItemNode = $doctrineAnnotationTagValueNode->getSilentValue();
        foreach ($oldToNewClasses as $oldClass => $newClass) {
            if ($classNameArrayItemNode instanceof ArrayItemNode && $classNameArrayItemNode->value instanceof StringNode) {
                $classNameStringNode = $classNameArrayItemNode->value;
                if ($classNameStringNode->value === $oldClass) {
                    $classNameStringNode->value = $newClass;
                    continue;
                }
                $this->renamedNameCollector->add($oldClass);
                $classNameStringNode->value = Strings::replace($classNameStringNode->value, '#\b' . preg_quote($oldClass, '#') . 'b#', $newClass);
                $classNameArrayItemNode->setAttribute(PhpDocAttributeKey::ORIG_NODE, null);
                $hasChanged = \true;
            }
            $currentTypeArrayItemNode = $doctrineAnnotationTagValueNode->getValue('type');
            if (!$currentTypeArrayItemNode instanceof ArrayItemNode) {
                continue;
            }
            $currentTypeStringNode = $currentTypeArrayItemNode->value;
            if (!$currentTypeStringNode instanceof StringNode) {
                continue;
            }
            if ($currentTypeStringNode->value === $oldClass) {
                $currentTypeStringNode->value = $newClass;
                $hasChanged = \true;
            }
        }
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    private function processDoctrineToMany(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode, Node $node, array $oldToNewClasses, bool &$hasChanged): void
    {
        $classKey = $doctrineAnnotationTagValueNode->hasClassName('Doctrine\ORM\Mapping\Embedded') ? 'class' : 'targetEntity';
        $targetEntityArrayItemNode = $doctrineAnnotationTagValueNode->getValue($classKey);
        if (!$targetEntityArrayItemNode instanceof ArrayItemNode) {
            return;
        }
        $targetEntityStringNode = $targetEntityArrayItemNode->value;
        if (!$targetEntityStringNode instanceof StringNode) {
            return;
        }
        $targetEntityClass = $targetEntityStringNode->value;
        // resolve to FQN
        $tagFullyQualifiedName = $this->classAnnotationMatcher->resolveTagFullyQualifiedName($targetEntityClass, $node);
        foreach ($oldToNewClasses as $oldClass => $newClass) {
            if ($tagFullyQualifiedName !== $oldClass) {
                continue;
            }
            $this->renamedNameCollector->add($oldClass);
            $targetEntityStringNode->value = $newClass;
            $targetEntityArrayItemNode->setAttribute(PhpDocAttributeKey::ORIG_NODE, null);
            $hasChanged = \true;
        }
    }
}
