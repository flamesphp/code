<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\Enum;

final class SymfonyAnnotation
{
    public const string ROUTE = 'Symfony\Component\Routing\Annotation\Route';
    public const string TWIG_TEMPLATE = 'Symfony\Bridge\Twig\Attribute\Template';
    public const string MAP_ENTITY = 'Symfony\Bridge\Doctrine\Attribute\MapEntity';
}
