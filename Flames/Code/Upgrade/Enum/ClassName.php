<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Enum;

final class ClassName
{
    public const string TEST_CASE_CLASS = \PHPUnit\Framework\TestCase::class;
    public const string MOCK_OBJECT = 'PHPUnit\Framework\MockObject\MockObject';
    public const string DATE_TIME_INTERFACE = 'DateTimeInterface';
    public const string JMS_TYPE = 'JMS\Serializer\Annotation\Type';
    public const string DOCTRINE_ENTITY = 'Doctrine\ORM\Mapping\Entity';
}
