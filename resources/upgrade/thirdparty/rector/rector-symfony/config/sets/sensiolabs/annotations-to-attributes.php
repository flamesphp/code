<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php80\Rector\Class_\AnnotationToAttributeRector;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // @see https://github.com/sensiolabs/SensioFrameworkExtraBundle/pull/707
        new AnnotationToAttribute('Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache'),
        new AnnotationToAttribute('Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity'),
        new AnnotationToAttribute('Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted'),
        new AnnotationToAttribute('Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter'),
        new AnnotationToAttribute('Sensio\Bundle\FrameworkExtraBundle\Configuration\Security'),
        new AnnotationToAttribute('Sensio\Bundle\FrameworkExtraBundle\Configuration\Template'),
    ]);
};
