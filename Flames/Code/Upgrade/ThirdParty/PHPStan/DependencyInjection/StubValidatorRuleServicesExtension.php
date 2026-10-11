<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Flames\Code\Upgrade\ThirdParty\Nette\DI\CompilerExtension;
use Flames\Code\Upgrade\ThirdParty\olvlvl\ComposerAttributeCollector\Attributes;
use Override;
use PHPStan\PhpDoc\StubValidator;
use function strcasecmp;
use function strtolower;
final class StubValidatorRuleServicesExtension extends CompilerExtension
{
    #[Override]
    public function loadConfiguration(): void
    {
        require_once __DIR__ . '/../attributes.php';
        $builder = $this->getContainerBuilder();
        $autowiredParameters = Attributes::findTargetMethodParameters(\PHPStan\DependencyInjection\AutowiredParameter::class);
        $constructorParameters = [];
        foreach ($autowiredParameters as $parameter) {
            if (strcasecmp($parameter->method, '__construct') !== 0) {
                continue;
            }
            $lowerClass = strtolower($parameter->class);
            $constructorParameters[$lowerClass] ??= [];
            $constructorParameters[$lowerClass][] = $parameter;
        }
        foreach (Attributes::findTargetClasses(\PHPStan\DependencyInjection\ValidatesStubFiles::class) as $class) {
            $definition = $builder->addDefinition(null)->setFactory($class->name)->setAutowired(\false)->addTag(StubValidator::SERVICE_RULE_TAG);
            \PHPStan\DependencyInjection\AutowiredAttributeServicesExtension::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
        }
    }
}
