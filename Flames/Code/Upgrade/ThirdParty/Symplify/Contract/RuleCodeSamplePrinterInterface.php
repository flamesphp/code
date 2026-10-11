<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Symplify\Contract;

use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
interface RuleCodeSamplePrinterInterface
{
    public function isMatch(string $class): bool;
    /**
     * @return string[]
     */
    public function print(\Flames\Code\Upgrade\ThirdParty\Symplify\Contract\CodeSampleInterface $codeSample, RuleDefinition $ruleDefinition): array;
}
