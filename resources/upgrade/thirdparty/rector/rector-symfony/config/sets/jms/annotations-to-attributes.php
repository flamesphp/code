<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php80\Rector\Class_\AnnotationToAttributeRector;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute;
use Flames\Code\Upgrade\JMS\Rector\Class_\AccessTypeAnnotationToAttributeRector;
use Flames\Code\Upgrade\JMS\Rector\Property\AccessorAnnotationToAttributeRector;
/**
 * @see https://github.com/schmittjoh/serializer/pull/1320
 * @see https://github.com/schmittjoh/serializer/pull/1332
 * @see https://github.com/schmittjoh/serializer/pull/1337
 */
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [new AnnotationToAttribute('JMS\Serializer\Annotation\AccessorOrder'), new AnnotationToAttribute('JMS\Serializer\Annotation\Discriminator'), new AnnotationToAttribute('JMS\Serializer\Annotation\Exclude'), new AnnotationToAttribute('JMS\Serializer\Annotation\ExclusionPolicy'), new AnnotationToAttribute('JMS\Serializer\Annotation\Expose'), new AnnotationToAttribute('JMS\Serializer\Annotation\Groups'), new AnnotationToAttribute('JMS\Serializer\Annotation\Inline'), new AnnotationToAttribute('JMS\Serializer\Annotation\MaxDepth'), new AnnotationToAttribute('JMS\Serializer\Annotation\PostDeserialize'), new AnnotationToAttribute('JMS\Serializer\Annotation\PostSerialize'), new AnnotationToAttribute('JMS\Serializer\Annotation\PreSerialize'), new AnnotationToAttribute('JMS\Serializer\Annotation\ReadOnly'), new AnnotationToAttribute('JMS\Serializer\Annotation\ReadOnlyProperty'), new AnnotationToAttribute('JMS\Serializer\Annotation\SerializedName'), new AnnotationToAttribute('JMS\Serializer\Annotation\Since'), new AnnotationToAttribute('JMS\Serializer\Annotation\SkipWhenEmpty'), new AnnotationToAttribute('JMS\Serializer\Annotation\Type'), new AnnotationToAttribute('JMS\Serializer\Annotation\Until'), new AnnotationToAttribute('JMS\Serializer\Annotation\VirtualProperty'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlAttributeMap'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlAttribute'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlDiscriminator'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlElement'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlKeyValuePairs'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlList'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlMap'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlNamespace'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlRoot'), new AnnotationToAttribute('JMS\Serializer\Annotation\XmlValue')]);
    $rectorConfig->rules([AccessTypeAnnotationToAttributeRector::class, AccessorAnnotationToAttributeRector::class]);
};
