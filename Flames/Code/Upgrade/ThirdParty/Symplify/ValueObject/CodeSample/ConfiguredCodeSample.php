<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample;

use Flames\Code\Upgrade\ThirdParty\Symplify\Contract\CodeSampleInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\AbstractCodeSample;
final class ConfiguredCodeSample extends AbstractCodeSample implements CodeSampleInterface
{
    /**
     * @var mixed[]
     */
    private array $configuration = [];
    /**
     * @param mixed[] $configuration
     */
    public function __construct(string $badCode, string $goodCode, array $configuration)
    {
        if ($configuration === []) {
            $message = sprintf('Configuration cannot be empty. Look for "%s"', $badCode);
            throw new ShouldNotHappenException($message);
        }
        $this->configuration = $configuration;
        parent::__construct($badCode, $goodCode);
    }
    /**
     * @return mixed[]
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
