<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use FlamesPrefix202610\PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use FlamesPrefix202610\PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use FlamesPrefix202610\PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use FlamesPrefix202610\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use FlamesPrefix202610\Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use FlamesPrefix202610\Symplify\EasyCodingStandard\ValueObject\Option;
return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::SKIP, [__DIR__ . '/tests/PropertiesPrinterHelper.php']);
    $containerConfigurator->import(__DIR__ . '/vendor/lmc/coding-standard/ecs.php');
    $services = $containerConfigurator->services();
    // Use single-line phpdoc where possible
    $services->set(PhpdocLineSpanFixer::class)->call('configure', [['property' => 'single']]);
    // Tests must have @test annotation
    $services->set(PhpUnitTestAnnotationFixer::class)->call('configure', [['style' => 'annotation']]);
    $services->set(OrderedClassElementsFixer::class);
    // Force line length
    $services->set(LineLengthFixer::class)->call('configure', [['line_length' => 120, 'break_long_lines' => \true, 'inline_short_lines' => \false]]);
};
