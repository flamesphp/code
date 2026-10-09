<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\ValueObject;

use Flames\Code\Upgrade\Validation\RectorAssert;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class IntlBundleClassToNewClass
{
    /**
     * @param array<string, string> $oldToNewMethods
     */
    public function __construct(private string $oldClass, private string $newClass, private array $oldToNewMethods)
    {
        RectorAssert::className($this->oldClass);
        RectorAssert::className($this->newClass);
        Assert::allString($this->oldToNewMethods);
        Assert::allString(array_keys($this->oldToNewMethods));
    }
    public function getOldClass(): string
    {
        return $this->oldClass;
    }
    public function getNewClass(): string
    {
        return $this->newClass;
    }
    /**
     * @return array<string, string>
     */
    public function getOldToNewMethods(): array
    {
        return $this->oldToNewMethods;
    }
}
