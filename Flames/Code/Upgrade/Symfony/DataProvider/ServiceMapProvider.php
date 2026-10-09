<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\DataProvider;

use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\Symfony\ValueObject\ServiceMap\ServiceMap;
use Flames\Code\Upgrade\Symfony\ValueObjectFactory\ServiceMapFactory;
/**
 * Inspired by https://github.com/phpstan/phpstan-symfony/tree/master/src/Symfony
 */
final class ServiceMapProvider
{
    private ?ServiceMap $serviceMap = null;
    public function __construct(private readonly ServiceMapFactory $serviceMapFactory)
    {
    }
    public function provide(): ServiceMap
    {
        // avoid caching in tests
        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            $this->serviceMap = null;
        }
        if ($this->serviceMap instanceof ServiceMap) {
            return $this->serviceMap;
        }
        if (SimpleParameterProvider::hasParameter(Option::SYMFONY_CONTAINER_XML_PATH_PARAMETER)) {
            $symfonyContainerXmlPath = SimpleParameterProvider::provideStringParameter(Option::SYMFONY_CONTAINER_XML_PATH_PARAMETER);
            $this->serviceMap = $this->serviceMapFactory->createFromFileContent($symfonyContainerXmlPath);
        } else {
            $this->serviceMap = $this->serviceMapFactory->createEmpty();
        }
        return $this->serviceMap;
    }
}
