<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\OndraM\Ci;

use Flames\Code\Upgrade\ThirdParty\OndraM\CiDetector;
use Flames\Code\Upgrade\ThirdParty\OndraM\Env;
use Flames\Code\Upgrade\ThirdParty\OndraM\TrinaryLogic;
class TeamCity extends AbstractCi
{
    public static function isDetected(Env $env): bool
    {
        return $env->get('TEAMCITY_VERSION') !== \false;
    }
    public function getCiName(): string
    {
        return CiDetector::CI_TEAMCITY;
    }
    public function isPullRequest(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getBuildNumber(): string
    {
        return $this->env->getString('BUILD_NUMBER');
    }
    public function getBuildUrl(): string
    {
        return '';
        // unsupported
    }
    public function getCommit(): string
    {
        return $this->env->getString('BUILD_VCS_NUMBER');
    }
    public function getBranch(): string
    {
        return '';
        // unsupported
    }
    public function getTargetBranch(): string
    {
        return '';
        // unsupported
    }
    public function getRepositoryName(): string
    {
        return '';
        // unsupported
    }
    public function getRepositoryUrl(): string
    {
        return '';
        // unsupported
    }
}
