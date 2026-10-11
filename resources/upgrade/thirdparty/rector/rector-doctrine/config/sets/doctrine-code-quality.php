<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Property\CorrectDefaultTypesOnEntityPropertyRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Property\TypedPropertyFromColumnTypeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Property\TypedPropertyFromToOneRelationTypeRector;
use Flames\Code\Upgrade\TypedCollections\Rector\Class_\CompleteReturnDocblockFromToManyRector;
use Flames\Code\Upgrade\Rules\Transform\Rector\Attribute\AttributeKeyToClassConstFetchRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\AttributeKeyToClassConstFetch;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        CorrectDefaultTypesOnEntityPropertyRector::class,
        // typed properties in entities from annotations/attributes
        TypedPropertyFromColumnTypeRector::class,
        TypedPropertyFromToOneRelationTypeRector::class,
        CompleteReturnDocblockFromToManyRector::class,
    ]);
    $rectorConfig->ruleWithConfiguration(AttributeKeyToClassConstFetchRector::class, [new AttributeKeyToClassConstFetch('Doctrine\ORM\Mapping\Column', 'type', 'Doctrine\DBAL\Types\Types', ['array' => 'ARRAY', 'ascii_string' => 'ASCII_STRING', 'bigint' => 'BIGINT', 'binary' => 'BINARY', 'blob' => 'BLOB', 'boolean' => 'BOOLEAN', 'date' => 'DATE_MUTABLE', 'date_immutable' => 'DATE_IMMUTABLE', 'dateinterval' => 'DATEINTERVAL', 'datetime' => 'DATETIME_MUTABLE', 'datetime_immutable' => 'DATETIME_IMMUTABLE', 'datetimetz' => 'DATETIMETZ_MUTABLE', 'datetimetz_immutable' => 'DATETIMETZ_IMMUTABLE', 'decimal' => 'DECIMAL', 'float' => 'FLOAT', 'guid' => 'GUID', 'integer' => 'INTEGER', 'json' => 'JSON', 'object' => 'OBJECT', 'simple_array' => 'SIMPLE_ARRAY', 'smallint' => 'SMALLINT', 'string' => 'STRING', 'text' => 'TEXT', 'time' => 'TIME_MUTABLE', 'time_immutable' => 'TIME_IMMUTABLE'])]);
};
