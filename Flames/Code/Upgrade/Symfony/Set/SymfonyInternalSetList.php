<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\Set;

/**
 * @api use in UpgradeConfig class
 * @internal Do not use outside of Upgrade core. Might change any time.
 */
final class SymfonyInternalSetList
{
    public const string JMS_ANNOTATIONS_TO_ATTRIBUTES = __DIR__ . '/../../config/sets/jms/annotations-to-attributes.php';
    public const string FOS_REST_ANNOTATIONS_TO_ATTRIBUTES = __DIR__ . '/../../config/sets/fosrest/annotations-to-attributes.php';
    public const string SENSIOLABS_ANNOTATIONS_TO_ATTRIBUTES = __DIR__ . '/../../config/sets/sensiolabs/annotations-to-attributes.php';
}
