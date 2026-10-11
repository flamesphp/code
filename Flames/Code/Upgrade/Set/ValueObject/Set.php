<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Set\ValueObject;

use Flames\Code\Upgrade\Set\Contract\SetInterface;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @api used by extensions
 *
 * @deprecated Bond the rules themselves instead, by implementing the ComposerPackageConstraintInterface. A set
 * described as an object only existed to be matched against the installed packages; a bonded rule states the exact
 * package version its target API is available from and applies from there upwards, so a plain set file is enough.
 *
 * @see \Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface
 * @see https://github.com/rectorphp/rector-src/pull/8296
 */
final readonly class Set implements SetInterface
{
    public function __construct(private string $groupName, private string $setName, private string $setFilePath)
    {
        Assert::fileExists($this->setFilePath);
    }
    public function getGroupName(): string
    {
        return $this->groupName;
    }
    public function getName(): string
    {
        return $this->setName;
    }
    public function getSetFilePath(): string
    {
        return $this->setFilePath;
    }
}
